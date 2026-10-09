<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Prize;
use OpenSpout\Reader\XLSX\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_prize_can_be_added_and_state_lists_it(): void
    {
        $response = $this->postJson(route('doorprize.prizes.store'), ['name' => '  Sepeda   Motor ', 'quantity' => 2])
            ->assertOk();

        $this->assertSame('Sepeda Motor', Prize::first()->name);
        $this->assertSame(2, $response->json('prizes.0.quantity'));
        $this->assertSame(2, $response->json('prizes.0.remaining'));
        $this->assertSame(0, $response->json('prizes.0.awarded'));
    }

    public function test_prize_validation(): void
    {
        $this->postJson(route('doorprize.prizes.store'), ['name' => '', 'quantity' => 1])->assertStatus(422);
        $this->postJson(route('doorprize.prizes.store'), ['name' => 'Kulkas', 'quantity' => 0])->assertStatus(422);
        $this->postJson(route('doorprize.prizes.store'), ['name' => 'Kulkas', 'quantity' => 1000])->assertStatus(422);

        $this->assertSame(0, Prize::count());
    }

    public function test_prize_name_and_quantity_can_be_edited_inline(): void
    {
        $prize = Prize::factory()->create(['name' => 'Kulkas', 'quantity' => 2]);
        $url = route('doorprize.prizes.update', $prize);

        $this->patchJson($url, ['field' => 'name', 'value' => 'Kulkas 2 Pintu'])->assertOk();
        $this->patchJson($url, ['field' => 'quantity', 'value' => '4'])->assertOk()
            ->assertJsonPath('prizes.0.quantity', 4);

        $this->patchJson($url, ['field' => 'name', 'value' => '  '])->assertStatus(422);
        $this->patchJson($url, ['field' => 'quantity', 'value' => '0'])->assertStatus(422);
        $this->patchJson($url, ['field' => 'quantity', 'value' => 'abc'])->assertStatus(422);

        $prize->refresh();
        $this->assertSame('Kulkas 2 Pintu', $prize->name);
        $this->assertSame(4, $prize->quantity);
    }

    public function test_quantity_cannot_drop_below_prizes_already_won(): void
    {
        $prize = Prize::factory()->create(['quantity' => 3]);
        Participant::factory()->count(2)->create(['won_at' => now(), 'prize_id' => $prize->id]);

        $this->patchJson(route('doorprize.prizes.update', $prize), ['field' => 'quantity', 'value' => '1'])->assertStatus(422);
        $this->patchJson(route('doorprize.prizes.update', $prize), ['field' => 'quantity', 'value' => '2'])->assertOk();
    }

    public function test_prize_can_be_deleted_and_winners_keep_their_win(): void
    {
        $prize = Prize::factory()->create();
        $winner = Participant::factory()->create(['won_at' => now(), 'prize_id' => $prize->id]);

        $this->deleteJson(route('doorprize.prizes.destroy', $prize))->assertOk()->assertJsonPath('prizes', []);

        $this->assertSame(0, Prize::count());
        $this->assertNotNull($winner->refresh()->won_at);
        $this->assertNull($winner->prize_id);
    }

    public function test_spin_links_winners_to_the_chosen_prize_and_reduces_stock(): void
    {
        Participant::factory()->count(6)->create();
        $prize = Prize::factory()->create(['quantity' => 5]);

        $this->postJson(route('doorprize.spin.start'), ['mode' => 'category', 'slots' => 3, 'prize_id' => $prize->id])
            ->assertOk()->assertJsonPath('prize_plan', [$prize->id, $prize->id, $prize->id]);
        $stopped = $this->postJson(route('doorprize.spin.stop'))->assertOk()->json();

        $this->assertSame(3, Participant::where('prize_id', $prize->id)->count());
        $this->assertSame(3, $stopped['prizes'][0]['awarded']);
        $this->assertSame(2, $stopped['prizes'][0]['remaining']);
        $this->assertEqualsCanonicalizing($stopped['winners'], $stopped['prizes'][0]['winners']);
    }

    public function test_allocation_gives_each_slot_its_own_prize_in_order(): void
    {
        Participant::factory()->count(6)->create();
        $motor = Prize::factory()->create(['name' => 'Sepeda Motor', 'quantity' => 2]);
        $kulkas = Prize::factory()->create(['name' => 'Kulkas', 'quantity' => 5]);

        $started = $this->postJson(route('doorprize.spin.start'), [
            'mode' => 'category',
            'slots' => 5,
            'allocation' => [
                ['prize_id' => $motor->id, 'count' => 2],
                ['prize_id' => $kulkas->id, 'count' => 3],
            ],
        ])->assertOk()->json();

        $this->assertSame([$motor->id, $motor->id, $kulkas->id, $kulkas->id, $kulkas->id], $started['prize_plan']);

        $stopped = $this->postJson(route('doorprize.spin.stop'))->assertOk()->json();

        // Pemenang di slot ke-n mendapat hadiah ke-n.
        $this->assertSame(
            ['Sepeda Motor', 'Sepeda Motor', 'Kulkas', 'Kulkas', 'Kulkas'],
            array_column($stopped['winner_details'], 'prize'),
        );
        foreach ($stopped['winner_details'] as $i => $detail) {
            $expected = $i < 2 ? $motor->id : $kulkas->id;
            $this->assertSame($expected, Participant::where('name', $detail['name'])->value('prize_id'));
        }
        $this->assertSame(0, $stopped['prizes'][0]['remaining']);
        $this->assertSame(2, $stopped['prizes'][1]['remaining']);
    }

    public function test_allocation_can_use_five_different_prizes(): void
    {
        Participant::factory()->count(5)->create();
        $prizes = Prize::factory()->count(5)->create(['quantity' => 1]);

        $this->postJson(route('doorprize.spin.start'), [
            'mode' => 'category',
            'slots' => 5,
            'allocation' => $prizes->map(fn ($p) => ['prize_id' => $p->id, 'count' => 1])->all(),
        ])->assertOk();
        $this->postJson(route('doorprize.spin.stop'))->assertOk();

        $this->assertEqualsCanonicalizing(
            $prizes->pluck('id')->all(),
            Participant::pluck('prize_id')->all(),
        );
    }

    public function test_allocation_must_match_the_number_of_winners_and_the_stock(): void
    {
        Participant::factory()->count(6)->create();
        $a = Prize::factory()->create(['quantity' => 2]);
        $b = Prize::factory()->create(['quantity' => 5]);

        // Total alokasi 4, slot 5.
        $this->postJson(route('doorprize.spin.start'), [
            'mode' => 'category', 'slots' => 5,
            'allocation' => [['prize_id' => $a->id, 'count' => 2], ['prize_id' => $b->id, 'count' => 2]],
        ])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'harus sama dengan'));

        // Hadiah yang sama di dua baris dihitung gabungan: 2 + 1 melebihi stok 2.
        $this->postJson(route('doorprize.spin.start'), [
            'mode' => 'category', 'slots' => 4,
            'allocation' => [['prize_id' => $a->id, 'count' => 2], ['prize_id' => $a->id, 'count' => 1], ['prize_id' => $b->id, 'count' => 1]],
        ])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'Sisa hadiah'));

        $this->postJson(route('doorprize.spin.start'), [
            'mode' => 'category', 'slots' => 1,
            'allocation' => [['prize_id' => 9999, 'count' => 1]],
        ])->assertStatus(422);

        $this->assertSame('idle', $this->getJson(route('doorprize.tv.state'))->json('status'));
    }

    public function test_spin_is_refused_when_the_prize_stock_is_too_small(): void
    {
        Participant::factory()->count(6)->create();
        $prize = Prize::factory()->create(['quantity' => 2]);

        $this->postJson(route('doorprize.spin.start'), ['mode' => 'category', 'slots' => 3, 'prize_id' => $prize->id])
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Sisa hadiah'));

        $this->postJson(route('doorprize.spin.start'), ['mode' => 'category', 'slots' => 3, 'prize_id' => 9999])
            ->assertStatus(422);

        $this->assertSame('idle', $this->getJson(route('doorprize.tv.state'))->json('status'));
    }

    public function test_reset_returns_the_stock(): void
    {
        Participant::factory()->count(4)->create();
        $prize = Prize::factory()->create(['quantity' => 2]);

        $this->postJson(route('doorprize.spin.start'), ['mode' => 'category', 'slots' => 2, 'prize_id' => $prize->id])->assertOk();
        $this->postJson(route('doorprize.spin.stop'))->assertOk();
        $this->assertSame(0, $prize->fresh()->remaining());

        $this->postJson(route('doorprize.spin.reset'))->assertOk();

        $this->assertSame(2, $prize->fresh()->remaining());
        $this->assertSame(0, Participant::whereNotNull('prize_id')->count());
    }

    public function test_deleting_the_prize_during_a_spin_does_not_break_stop(): void
    {
        Participant::factory()->count(3)->create();
        $prize = Prize::factory()->create(['quantity' => 2]);

        $this->postJson(route('doorprize.spin.start'), ['mode' => 'category', 'slots' => 2, 'prize_id' => $prize->id])->assertOk();
        $this->deleteJson(route('doorprize.prizes.destroy', $prize))->assertOk();

        $this->postJson(route('doorprize.spin.stop'))->assertOk()->assertJsonCount(2, 'winners');
        $this->assertSame(0, Participant::whereNotNull('prize_id')->count());
    }

    public function test_prize_can_be_created_with_an_image(): void
    {
        Storage::fake('public');

        $response = $this->postJson(route('doorprize.prizes.store'), [
            'name' => 'Sepeda Motor',
            'quantity' => 1,
            'image' => UploadedFile::fake()->image('motor.jpg', 400, 300),
        ])->assertOk();

        $prize = Prize::first();
        Storage::disk('public')->assertExists($prize->image_path);
        $this->assertStringStartsWith('prizes/', $prize->image_path);
        $this->assertStringEndsWith('/storage/'.$prize->image_path, $response->json('prizes.0.image_url'));
    }

    public function test_prize_without_image_has_no_image_url(): void
    {
        $this->postJson(route('doorprize.prizes.store'), ['name' => 'Kulkas', 'quantity' => 1])
            ->assertOk()->assertJsonPath('prizes.0.image_url', null);
    }

    public function test_prize_image_must_be_a_small_picture(): void
    {
        Storage::fake('public');

        $this->postJson(route('doorprize.prizes.store'), [
            'name' => 'A', 'quantity' => 1, 'image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ])->assertStatus(422);

        $this->postJson(route('doorprize.prizes.store'), [
            'name' => 'B', 'quantity' => 1, 'image' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ])->assertStatus(422);

        $this->assertSame(0, Prize::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_replacing_and_removing_the_image_cleans_up_the_old_file(): void
    {
        Storage::fake('public');
        $prize = Prize::factory()->create();

        $this->postJson(route('doorprize.prizes.image.store', $prize), ['image' => UploadedFile::fake()->image('a.png')])->assertOk();
        $first = $prize->fresh()->image_path;
        Storage::disk('public')->assertExists($first);

        $this->postJson(route('doorprize.prizes.image.store', $prize), ['image' => UploadedFile::fake()->image('b.png')])->assertOk();
        $second = $prize->fresh()->image_path;
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->deleteJson(route('doorprize.prizes.image.destroy', $prize))->assertOk()->assertJsonPath('prizes.0.image_url', null);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($prize->fresh()->image_path);

        $this->postJson(route('doorprize.prizes.image.store', $prize), [])->assertStatus(422);
    }

    public function test_deleting_a_prize_removes_its_image_file(): void
    {
        Storage::fake('public');
        $prize = Prize::factory()->create();
        $this->postJson(route('doorprize.prizes.image.store', $prize), ['image' => UploadedFile::fake()->image('a.png')])->assertOk();
        $path = $prize->fresh()->image_path;

        $this->deleteJson(route('doorprize.prizes.destroy', $prize))->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_winner_export_lists_each_winner_with_the_prize_they_won(): void
    {
        $motor = Prize::factory()->create(['name' => 'Sepeda Motor']);
        Participant::factory()->create(['name' => 'Budi', 'category' => 'Keluarga CPP', 'won_at' => now()->subMinutes(2), 'prize_id' => $motor->id]);
        Participant::factory()->create(['name' => 'Rina', 'category' => 'Umum', 'won_at' => now()->subMinute()]);
        Participant::factory()->create(['name' => 'Belum Menang']);

        $response = $this->get(route('doorprize.winners.export'))->assertOk();
        $this->assertStringContainsString('daftar-pemenang-doorprize.xlsx', $response->headers->get('content-disposition'));

        $path = tempnam(sys_get_temp_dir(), 'win');
        file_put_contents($path, $response->streamedContent());

        $reader = new Reader;
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        $this->assertSame(['No', 'Nama Pemenang', 'Kategori', 'Hadiah', 'Waktu Menang'], $rows[0]);
        $this->assertCount(3, $rows);
        $this->assertSame([1, 'Budi', 'Tamu Keluarga CPP', 'Sepeda Motor'], array_slice($rows[1], 0, 4));
        $this->assertSame([2, 'Rina', 'Umum', '-'], array_slice($rows[2], 0, 4));
    }

    public function test_dashboard_shows_the_prize_list_and_selector_without_the_winner_history_card(): void
    {
        Prize::factory()->create(['name' => 'Sepeda Motor']);

        $this->get(route('doorprize'))->assertOk()
            ->assertSee('id="prize-card"', false)
            ->assertSee('data-allocation-rows', false)
            ->assertSee('id="prize-image-input"', false)
            ->assertSee('id="prize-modal"', false)
            ->assertSee('data-bs-target="#prize-modal"', false)
            ->assertSee('Sepeda Motor')
            ->assertDontSee('winners-history-card', false);
    }
}
