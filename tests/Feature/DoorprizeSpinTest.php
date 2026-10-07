<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoorprizeSpinTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_state_is_idle(): void
    {
        $this->getJson(route('doorprize.tv.state'))->assertOk()
            ->assertJson([
                'status' => 'idle',
                'seq' => 0,
                'winners' => [],
                'slots' => 5,
                'categories' => [],
                'eligible' => 0,
                'eligible_total' => 0,
                'won' => 0,
                'duration' => 0,
                'remaining_ms' => null,
            ]);
    }

    public function test_start_needs_at_least_five_participants(): void
    {
        Participant::factory()->count(4)->create();

        $this->postJson(route('doorprize.spin.start'))->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'Minimal 5'));
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'idle');
    }

    public function test_start_then_stop_picks_five_unique_winners_from_the_participants(): void
    {
        $names = Participant::factory()->count(12)->create()->pluck('name')->all();

        $this->postJson(route('doorprize.spin.start'))->assertOk()->assertJson(['status' => 'spinning', 'seq' => 1, 'winners' => []]);

        $stopped = $this->postJson(route('doorprize.spin.stop'))->assertOk()->assertJson(['status' => 'stopped', 'seq' => 2])->json();

        $this->assertCount(5, $stopped['winners']);
        $this->assertCount(5, array_unique($stopped['winners']));
        $this->assertEmpty(array_diff($stopped['winners'], $names));
        $this->assertEqualsCanonicalizing($stopped['winners'], Participant::whereNotNull('won_at')->pluck('name')->all());
        $this->assertSame(7, Participant::whereNull('won_at')->count());
        $this->getJson(route('doorprize.tv.state'))->assertJson($stopped);
    }

    public function test_stop_without_start_is_refused(): void
    {
        Participant::factory()->count(5)->create();

        $this->postJson(route('doorprize.spin.stop'))->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'belum dimulai'));
    }

    public function test_starting_twice_does_not_restart_the_spin(): void
    {
        Participant::factory()->count(5)->create();

        $this->postJson(route('doorprize.spin.start'))->assertJsonPath('seq', 1);
        $this->postJson(route('doorprize.spin.start'))->assertJsonPath('seq', 1);
    }

    public function test_start_after_stop_clears_the_previous_winners(): void
    {
        Participant::factory()->count(10)->create();
        $this->postJson(route('doorprize.spin.start'));
        $this->postJson(route('doorprize.spin.stop'));

        $this->postJson(route('doorprize.spin.start'))->assertJson(['status' => 'spinning', 'seq' => 3, 'winners' => [], 'eligible' => 5]);
    }

    public function test_stop_is_refused_if_participants_were_removed_meanwhile(): void
    {
        $participants = Participant::factory()->count(5)->create();
        $this->postJson(route('doorprize.spin.start'));
        $participants->first()->delete();

        $this->postJson(route('doorprize.spin.stop'))->assertStatus(422);
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'spinning');
    }

    public function test_stop_before_any_start_is_refused_with_a_clear_message(): void
    {
        $this->postJson(route('doorprize.spin.stop'))->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'belum dimulai'));
    }

    public function test_stop_after_the_draw_already_finished_returns_the_same_result(): void
    {
        Participant::factory()->count(6)->create();
        $this->postJson(route('doorprize.spin.start'));
        $first = $this->postJson(route('doorprize.spin.stop'))->json();

        $again = $this->postJson(route('doorprize.spin.stop'))->assertOk()->json();

        $this->assertSame($first['winners'], $again['winners']);
        $this->assertSame($first['seq'], $again['seq']);
        $this->assertSame(5, Participant::whereNotNull('won_at')->count());   // tidak memilih pemenang kedua kali
    }

    public function test_the_name_pool_is_sent_only_to_a_tv_that_does_not_know_the_spin_yet(): void
    {
        Participant::factory()->count(6)->create();
        $this->postJson(route('doorprize.spin.start'));

        $this->getJson(route('doorprize.tv.state', ['seq' => 0]))->assertJsonCount(6, 'pool');   // TV belum tahu putaran ini
        $this->getJson(route('doorprize.tv.state', ['seq' => 1]))->assertJsonMissingPath('pool'); // sudah tahu
        $this->getJson(route('doorprize.tv.state'))->assertJsonMissingPath('pool');               // dasbor tidak butuh
    }

    public function test_winners_stay_marked_after_the_next_draw_and_status_is_listed(): void
    {
        Participant::factory()->count(10)->create();

        $this->postJson(route('doorprize.spin.start'));
        $first = $this->postJson(route('doorprize.spin.stop'))->json('winners');
        $this->postJson(route('doorprize.spin.start'));
        $second = $this->postJson(route('doorprize.spin.stop'))->json('winners');

        // Pemenang putaran pertama tetap berstatus pemenang (riwayat), plus pemenang putaran kedua.
        $marked = Participant::whereNotNull('won_at')->pluck('name')->all();
        $this->assertEqualsCanonicalizing(array_unique([...$first, ...$second]), $marked);

        $html = $this->get(route('doorprize'))->assertOk()->getContent();
        $this->assertSame(count($marked), substr_count($html, 'data-status="PEMENANG"'));
    }

    public function test_a_winner_is_never_drawn_again(): void
    {
        Participant::factory()->count(10)->create();

        $this->postJson(route('doorprize.spin.start'));
        $first = $this->postJson(route('doorprize.spin.stop'))->assertJsonPath('eligible', 5)->json('winners');

        $this->postJson(route('doorprize.spin.start'))->assertOk();
        $second = $this->postJson(route('doorprize.spin.stop'))->assertJsonPath('eligible', 0)->json('winners');

        $this->assertSame([], array_intersect($first, $second));
        $this->assertCount(10, array_unique([...$first, ...$second]));
    }

    public function test_start_is_refused_when_fewer_than_five_participants_have_not_won_yet(): void
    {
        Participant::factory()->count(8)->create();
        $this->postJson(route('doorprize.spin.start'));
        $this->postJson(route('doorprize.spin.stop'));      // 5 menang, tersisa 3

        $this->postJson(route('doorprize.spin.start'))->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'belum menang') && str_contains($m, 'tersisa 3'));
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'stopped')->assertJsonPath('eligible', 3);
    }

    public function test_tv_name_pool_and_page_exclude_previous_winners(): void
    {
        Participant::factory()->count(10)->create();
        $this->postJson(route('doorprize.spin.start'));
        $winners = $this->postJson(route('doorprize.spin.stop'))->json('winners');
        $this->postJson(route('doorprize.spin.start'));

        $pool = $this->getJson(route('doorprize.tv.state', ['seq' => 0]))->json('pool');
        $this->assertCount(5, $pool);
        $this->assertSame([], array_intersect($pool, $winners));

        $this->get(route('doorprize.tv'))->assertViewHas('participants', fn ($p) => $p->count() === 5 && $p->intersect($winners)->isEmpty());
    }

    public function test_a_participant_who_never_won_has_no_status(): void
    {
        Participant::factory()->create(['name' => 'Belum Menang']);

        $this->get(route('doorprize'))->assertOk()
            ->assertSee('data-status=""', false)
            ->assertDontSee('data-status="PEMENANG"', false);
    }

    public function test_pages_receive_the_spin_state(): void
    {
        Participant::factory()->count(5)->create();
        $this->postJson(route('doorprize.spin.start'));

        $this->get(route('doorprize'))->assertOk()->assertSee('data-status="spinning"', false);
        $this->get(route('doorprize.tv'))->assertOk()->assertSee('window.doorprizeSpin', false)->assertViewHas('spin', fn ($s) => $s['status'] === 'spinning');
    }

    public function test_spin_duration_can_be_saved_and_validated(): void
    {
        $this->postJson(route('doorprize.spin.duration'), ['duration' => 12])->assertOk()->assertJsonPath('duration', 12);
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('duration', 12);

        foreach ([-1, 301, 'abc', 1.5, null] as $bad) {
            $this->postJson(route('doorprize.spin.duration'), ['duration' => $bad])->assertStatus(422)->assertJsonValidationErrors('duration');
        }
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('duration', 12);

        $this->postJson(route('doorprize.spin.duration'), ['duration' => 0])->assertOk()->assertJsonPath('duration', 0);
    }

    public function test_with_a_duration_the_spin_stops_by_itself_and_picks_the_winners_on_the_server(): void
    {
        Participant::factory()->count(8)->create();
        $this->postJson(route('doorprize.spin.duration'), ['duration' => 10]);

        $started = $this->postJson(route('doorprize.spin.start'))->assertOk()->assertJsonPath('status', 'spinning')->json();
        $this->assertEqualsWithDelta(10000, $started['remaining_ms'], 1500);

        $this->travel(5)->seconds();
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'spinning')
            ->assertJsonPath('remaining_ms', fn ($ms) => $ms > 3000 && $ms <= 5500);

        $this->travel(6)->seconds();   // total 11 detik: waktu habis, dievaluasi saat status dibaca
        $stopped = $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'stopped')->assertJsonPath('remaining_ms', null)->json();

        $this->assertCount(5, $stopped['winners']);
        $this->assertSame(5, Participant::whereNotNull('won_at')->count());
        $this->assertSame($started['seq'] + 1, $stopped['seq']);

        // Dibaca berkali-kali tetap satu putaran saja.
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('seq', $stopped['seq'])->assertJsonPath('winners', $stopped['winners']);
        $this->assertSame(5, Participant::whereNotNull('won_at')->count());
    }

    public function test_manual_stop_before_the_time_is_up_works_and_ends_the_countdown(): void
    {
        Participant::factory()->count(6)->create();
        $this->postJson(route('doorprize.spin.duration'), ['duration' => 30]);
        $this->postJson(route('doorprize.spin.start'));

        $this->travel(3)->seconds();
        $this->postJson(route('doorprize.spin.stop'))->assertOk()->assertJsonPath('status', 'stopped')->assertJsonPath('remaining_ms', null);
    }

    public function test_without_a_duration_the_spin_never_stops_by_itself(): void
    {
        Participant::factory()->count(6)->create();
        $this->postJson(route('doorprize.spin.start'))->assertJsonPath('remaining_ms', null);

        $this->travel(1)->hours();
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'spinning');
    }

    public function test_changing_the_duration_does_not_affect_a_spin_in_progress(): void
    {
        Participant::factory()->count(6)->create();
        $this->postJson(route('doorprize.spin.duration'), ['duration' => 60]);
        $this->postJson(route('doorprize.spin.start'));

        $this->postJson(route('doorprize.spin.duration'), ['duration' => 1]);
        $this->travel(5)->seconds();

        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'spinning');
    }

    public function test_spin_that_expires_after_everyone_was_removed_goes_back_to_idle(): void
    {
        $participants = Participant::factory()->count(5)->create();
        $this->postJson(route('doorprize.spin.duration'), ['duration' => 5]);
        $this->postJson(route('doorprize.spin.start'));
        $participants->each->delete();

        $this->travel(6)->seconds();

        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'idle');
    }

    public function test_reset_clears_every_winner_status_and_blanks_the_tv_state(): void
    {
        Participant::factory()->count(10)->create();
        $this->postJson(route('doorprize.spin.start'));
        $this->postJson(route('doorprize.spin.stop'));
        $this->postJson(route('doorprize.spin.start'));
        $before = $this->postJson(route('doorprize.spin.stop'))->assertJsonPath('won', 10)->json();

        $this->postJson(route('doorprize.spin.reset'))->assertOk()
            ->assertJson(['reset' => 10, 'status' => 'idle', 'winners' => [], 'eligible' => 10, 'won' => 0])
            ->assertJsonPath('seq', $before['seq'] + 1);   // seq naik supaya TV tahu harus mengosongkan slot

        $this->assertSame(0, Participant::whereNotNull('won_at')->count());
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'idle')->assertJsonPath('winners', []);
    }

    public function test_participants_can_be_drawn_again_after_a_reset(): void
    {
        Participant::factory()->count(5)->create();
        $this->postJson(route('doorprize.spin.start'));
        $this->postJson(route('doorprize.spin.stop'));
        $this->postJson(route('doorprize.spin.start'))->assertStatus(422);   // semua sudah menang

        $this->postJson(route('doorprize.spin.reset'))->assertOk();

        $this->postJson(route('doorprize.spin.start'))->assertOk()->assertJsonPath('status', 'spinning');
        $this->postJson(route('doorprize.spin.stop'))->assertOk()->assertJsonCount(5, 'winners');
    }

    public function test_reset_is_refused_while_the_spin_is_running_and_changes_nothing(): void
    {
        Participant::factory()->count(10)->create();
        $this->postJson(route('doorprize.spin.start'));
        $this->postJson(route('doorprize.spin.stop'));
        $this->postJson(route('doorprize.spin.start'));

        $this->postJson(route('doorprize.spin.reset'))->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Stop'));

        $this->assertSame(5, Participant::whereNotNull('won_at')->count());
        $this->getJson(route('doorprize.tv.state'))->assertJsonPath('status', 'spinning');
    }

    public function test_reset_works_after_a_timed_spin_finished_by_itself_and_with_no_winners_at_all(): void
    {
        Participant::factory()->count(5)->create();
        $this->postJson(route('doorprize.spin.reset'))->assertOk()->assertJsonPath('reset', 0);   // tanpa pemenang: aman

        $this->postJson(route('doorprize.spin.duration'), ['duration' => 5]);
        $this->postJson(route('doorprize.spin.start'));
        $this->travel(6)->seconds();   // waktu habis; reset menyelesaikannya dulu, lalu menghapus statusnya

        $this->postJson(route('doorprize.spin.reset'))->assertOk()->assertJsonPath('reset', 5)->assertJsonPath('status', 'idle');
        $this->assertSame(0, Participant::whereNotNull('won_at')->count());
    }

    public function test_dashboard_shows_the_reset_button_with_its_endpoint(): void
    {
        $this->get(route('doorprize'))->assertOk()
            ->assertSee('data-spin-reset', false)
            ->assertSee(route('doorprize.spin.reset'), false);
    }

    public function test_can_spin_custom_number_of_slots(): void
    {
        Participant::factory()->count(10)->create();

        // Undi 3 orang
        $this->postJson(route('doorprize.spin.start'), ['slots' => 3])
            ->assertOk()
            ->assertJsonPath('slots', 3);

        $stopped = $this->postJson(route('doorprize.spin.stop'))->assertOk()->json();
        $this->assertCount(3, $stopped['winners']);
        $this->assertSame(3, Participant::whereNotNull('won_at')->count());
    }

    public function test_can_spin_from_selected_categories(): void
    {
        Participant::factory()->count(5)->create(['category' => 'Keluarga CPP']);
        Participant::factory()->count(5)->create(['category' => 'UMUM']);

        // Undi 2 orang khusus dari Keluarga CPP
        $this->postJson(route('doorprize.spin.start'), [
            'slots' => 2,
            'categories' => ['Keluarga CPP'],
        ])->assertOk();

        $stopped = $this->postJson(route('doorprize.spin.stop'))->assertOk()->json();
        $this->assertCount(2, $stopped['winners']);

        // Verifikasi semua pemenang berasal dari Keluarga CPP
        $winnerCategories = Participant::whereIn('name', $stopped['winners'])->pluck('category')->all();
        $this->assertSame(['Keluarga CPP', 'Keluarga CPP'], $winnerCategories);
    }

    public function test_refuses_spin_if_not_enough_participants_in_selected_category(): void
    {
        Participant::factory()->count(2)->create(['category' => 'Teman CPW']);
        Participant::factory()->count(10)->create(['category' => 'UMUM']);

        // Minta undi 3 orang dari Teman CPW padahal cuma ada 2
        $this->postJson(route('doorprize.spin.start'), [
            'slots' => 3,
            'categories' => ['Teman CPW'],
        ])->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Minimal 3'));
    }
}

