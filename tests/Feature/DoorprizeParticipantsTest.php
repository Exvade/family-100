<?php

namespace Tests\Feature;

use App\Livewire\ParticipantForm;
use App\Livewire\ParticipantImport;
use App\Livewire\ParticipantList;
use App\Models\Participant;
use App\Services\ParticipantSpreadsheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class DoorprizeParticipantsTest extends TestCase
{
    use RefreshDatabase;

    /** @param list<mixed> $names */
    private function xlsx(array $names, string $filename = 'peserta.xlsx'): \Illuminate\Http\Testing\File
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($names as $name) {
            $writer->addRow(Row::fromValues([$name]));
        }
        $writer->close();

        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    public function test_doorprize_page_lists_participants_in_the_datatable(): void
    {
        Participant::factory()->create(['name' => 'Budi Santoso']);
        Participant::factory()->create(['name' => 'Rina Marlina']);

        $this->get(route('doorprize'))->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('data-name="Budi Santoso"', false)
            ->assertSee('data-name="Rina Marlina"', false);
    }

    public function test_inline_edit_updates_name_category_and_status(): void
    {
        $participant = Participant::factory()->create(['name' => 'Budi', 'category' => 'Umum']);
        $url = route('doorprize.participants.update', $participant);

        $this->patchJson($url, ['field' => 'name', 'value' => '  Budi   Santoso '])
            ->assertOk()->assertJson(['name' => 'Budi Santoso']);
        $this->patchJson($url, ['field' => 'category', 'value' => 'Teman CPP'])
            ->assertOk()->assertJson(['category' => 'Teman CPP']);
        $this->patchJson($url, ['field' => 'status', 'value' => 'PEMENANG'])
            ->assertOk()->assertJson(['status' => 'PEMENANG']);

        $participant->refresh();
        $this->assertSame('Budi Santoso', $participant->name);
        $this->assertSame('Teman CPP', $participant->category);
        $this->assertTrue($participant->isWinner());

        $this->patchJson($url, ['field' => 'status', 'value' => ''])
            ->assertOk()->assertJson(['status' => '']);
        $this->assertFalse($participant->refresh()->isWinner());
    }

    public function test_inline_edit_rejects_invalid_values(): void
    {
        Participant::factory()->create(['name' => 'Rina']);
        $participant = Participant::factory()->create(['name' => 'Budi']);
        $url = route('doorprize.participants.update', $participant);

        $this->patchJson($url, ['field' => 'name', 'value' => ' '])->assertStatus(422);
        $this->patchJson($url, ['field' => 'name', 'value' => 'rina'])->assertStatus(422);
        $this->patchJson($url, ['field' => 'category', 'value' => 'Lainnya'])->assertStatus(422);
        $this->patchJson($url, ['field' => 'status', 'value' => 'MENANG'])->assertStatus(422);
        $this->patchJson($url, ['field' => 'nomor', 'value' => '3'])->assertStatus(422);

        $this->assertSame('Budi', $participant->refresh()->name);
    }

    public function test_participants_table_shows_the_win_time_column(): void
    {
        $winner = Participant::factory()->create(['name' => 'Budi', 'won_at' => now()->subMinutes(5)]);
        Participant::factory()->create(['name' => 'Rina']);

        $this->get(route('doorprize'))->assertOk()
            ->assertSee('data-dt-sort="wonAt"', false)
            ->assertSee('data-won-at="'.$winner->won_at->toIso8601String().'"', false)
            ->assertSee('data-col-won-at', false)
            ->assertSee($winner->won_at->diffForHumans());

        $rina = Participant::where('name', 'Rina')->first();
        $this->patchJson(route('doorprize.participants.update', $rina), ['field' => 'status', 'value' => 'PEMENANG'])
            ->assertOk()
            ->assertJsonPath('status', 'PEMENANG')
            ->assertJsonStructure(['won_at', 'won_at_human', 'won_at_formatted']);
    }

    public function test_doorprize_page_renders_with_its_components(): void
    {
        $this->get(route('doorprize'))->assertOk()
            ->assertSeeLivewire(ParticipantList::class)
            ->assertSeeLivewire(ParticipantForm::class)
            ->assertSeeLivewire(ParticipantImport::class);
    }

    public function test_tv_page_receives_the_participant_names(): void
    {
        Participant::factory()->create(['name' => 'Budi Santoso']);
        Participant::factory()->create(['name' => 'Rina Marlina']);

        $this->get(route('doorprize.tv'))->assertOk()
            ->assertViewHas('participants', fn ($p) => $p->all() === ['Budi Santoso', 'Rina Marlina'])
            ->assertSee('Budi Santoso');
    }

    /** @return array<string, list<mixed>> nama sheet => nilai kolom A tiap baris */
    private function readSheets(string $content): array
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($path, $content);

        $reader = new Reader;
        $reader->open($path);
        $sheets = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray()[0] ?? null;
            }
            $sheets[$sheet->getName()] = $rows;
        }
        $reader->close();

        return $sheets;
    }

    public function test_template_has_one_sheet_per_category_plus_instructions(): void
    {
        $response = $this->get(route('doorprize.template'))->assertOk();
        $this->assertStringContainsString('template-peserta-doorprize.xlsx', $response->headers->get('content-disposition'));

        $sheets = $this->readSheets($response->streamedContent());

        $this->assertSame(
            ['Tamu Keluarga CPP', 'Tamu Keluarga CPW', 'Teman CPW', 'Teman CPP', 'Umum', 'Petunjuk'],
            array_keys($sheets),
        );
        foreach (['Tamu Keluarga CPP', 'Tamu Keluarga CPW', 'Teman CPW', 'Teman CPP', 'Umum'] as $name) {
            $this->assertSame(['Nama Peserta'], $sheets[$name]);
        }
    }

    public function test_category_template_has_a_single_sheet_named_after_the_category(): void
    {
        $sheets = $this->readSheets($this->get(route('doorprize.template', ['category' => 'Teman CPP']))->assertOk()->streamedContent());

        $this->assertSame(['Teman CPP', 'Petunjuk'], array_keys($sheets));
        $this->assertSame(['Nama Peserta'], $sheets['Teman CPP']);
    }

    /** @param array<string, list<string>> $sheets nama sheet => nilai kolom A tiap baris */
    private function xlsxSheets(array $sheets, string $filename = 'peserta.xlsx'): \Illuminate\Http\Testing\File
    {
        $path = tempnam(sys_get_temp_dir(), 'xl');
        $writer = new Writer;
        $writer->openToFile($path);
        $first = true;
        foreach ($sheets as $name => $lines) {
            $first ? $writer->getCurrentSheet()->setName($name) : $writer->addNewSheetAndMakeItCurrent()->setName($name);
            $first = false;
            foreach ($lines as $line) {
                $writer->addRow(Row::fromValues([$line]));
            }
        }
        $writer->close();

        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    public function test_multi_sheet_import_gives_each_sheet_its_category_and_skips_the_instructions(): void
    {
        $file = $this->xlsxSheets([
            'Tamu Keluarga CPP' => ['Nama Peserta', 'Budi Santoso', 'Citra Dewi'],
            'Tamu Keluarga CPW' => ['Nama Peserta'],
            'Teman CPW' => ['Nama Peserta', 'Rina Marlina'],
            'Umum' => ['Nama Peserta', 'Dewi Lestari'],
            'Petunjuk' => ['Petunjuk Pengisian Template Per Kategori:', '1. Setiap kategori sudah memiliki Sheet tersendiri'],
        ]);

        Livewire::test(ParticipantImport::class)->set('file', $file)->call('save')->assertHasNoErrors();

        $this->assertSame('Keluarga CPP', Participant::where('name', 'Budi Santoso')->value('category'));
        $this->assertSame('Keluarga CPP', Participant::where('name', 'Citra Dewi')->value('category'));
        $this->assertSame('Teman CPW', Participant::where('name', 'Rina Marlina')->value('category'));
        $this->assertSame('Umum', Participant::where('name', 'Dewi Lestari')->value('category'));
        $this->assertDatabaseCount('participants', 4);
    }

    public function test_import_with_a_chosen_category_overrides_the_sheet_names(): void
    {
        $file = $this->xlsxSheets([
            'Tamu Keluarga CPP' => ['Nama Peserta', 'Budi Santoso'],
            'Umum' => ['Nama Peserta', 'Dewi Lestari'],
        ]);

        Livewire::test(ParticipantImport::class)
            ->set('category', 'Teman CPP')
            ->set('file', $file)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['Teman CPP', 'Teman CPP'], Participant::orderBy('id')->pluck('category')->all());
    }

    public function test_names_repeated_across_sheets_are_imported_once(): void
    {
        $file = $this->xlsxSheets([
            'Tamu Keluarga CPP' => ['Nama Peserta', 'Budi Santoso'],
            'Teman CPP' => ['Nama Peserta', 'budi  santoso', 'Rina Marlina'],
        ]);

        Livewire::test(ParticipantImport::class)->set('file', $file)->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('participants', 2);
        $this->assertSame('Keluarga CPP', Participant::where('name', 'Budi Santoso')->value('category'));
    }

    public function test_importing_the_untouched_template_adds_nothing(): void
    {
        $path = app(ParticipantSpreadsheet::class)->template();

        $this->assertSame(['added' => 0, 'duplicates' => 0, 'invalid' => 0], app(ParticipantSpreadsheet::class)->import($path, 'xlsx'));
    }

    public function test_xlsx_import_skips_header_blanks_and_duplicates(): void
    {
        Participant::factory()->create(['name' => 'Rina Marlina']);

        $file = $this->xlsx(['Nama Peserta', 'Budi  Santoso ', '', 'rina marlina', 'BUDI SANTOSO', 12345, str_repeat('x', 256), 'Citra Dewi']);

        Livewire::test(ParticipantImport::class)
            ->set('file', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('doorprize'));

        $this->assertEqualsCanonicalizing(['Rina Marlina', 'Budi Santoso', '12345', 'Citra Dewi'], Participant::pluck('name')->all());
        $this->assertSame('3 peserta ditambahkan, 2 dilewati karena nama sudah ada, 1 baris tidak valid.', session('status'));
    }

    public function test_a_first_row_that_is_a_real_name_is_kept_when_there_is_no_header(): void
    {
        $file = $this->xlsx(['Andi', 'Budi']);   // ditahan di variabel agar file sementara tidak terhapus

        $result = app(ParticipantSpreadsheet::class)->import($file->getRealPath(), 'xlsx');

        $this->assertSame(2, $result['added']);
    }

    public function test_csv_import_works(): void
    {
        $file = UploadedFile::fake()->createWithContent('peserta.csv', "Nama\nAni\nBudi\n");

        Livewire::test(ParticipantImport::class)->set('file', $file)->call('save')->assertHasNoErrors();

        $this->assertEqualsCanonicalizing(['Ani', 'Budi'], Participant::pluck('name')->all());
    }

    public function test_invalid_uploads_are_rejected_with_a_message(): void
    {
        Livewire::test(ParticipantImport::class)->call('save')->assertHasErrors(['file' => 'required']);

        Livewire::test(ParticipantImport::class)
            ->set('file', UploadedFile::fake()->create('foto.png', 10, 'image/png'))
            ->call('save')
            ->assertHasErrors(['file' => 'mimes']);

        Livewire::test(ParticipantImport::class)
            ->set('file', UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan file excel'))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_too_many_rows_are_refused_and_nothing_is_imported(): void
    {
        // MAX_ROWS peserta masih boleh (judul kolom tidak dihitung); satu lagi ditolak.
        $names = array_map(fn ($i) => "Peserta $i", range(1, ParticipantSpreadsheet::MAX_ROWS + 1));

        Livewire::test(ParticipantImport::class)
            ->set('file', $this->xlsx($names))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('participants', 0);   // tidak ada yang masuk setengah jalan
    }

    public function test_exactly_the_maximum_number_of_rows_is_accepted_with_a_header(): void
    {
        $names = ['Nama Peserta', ...array_map(fn ($i) => "Peserta $i", range(1, ParticipantSpreadsheet::MAX_ROWS))];

        Livewire::test(ParticipantImport::class)->set('file', $this->xlsx($names))->call('save')->assertHasNoErrors();

        $this->assertDatabaseCount('participants', ParticipantSpreadsheet::MAX_ROWS);
    }

    public function test_participant_can_be_added_manually(): void
    {
        Livewire::test(ParticipantForm::class)
            ->dispatch('open-participant-form', id: null)
            ->assertDispatched('participant-form-ready')
            ->set('name', '  Dewi   Lestari ')
            ->call('save')
            ->assertRedirect(route('doorprize'));

        $this->assertDatabaseHas('participants', ['name' => 'Dewi Lestari']);
        $this->assertSame('Peserta ditambahkan.', session('status'));
    }

    public function test_manual_add_rejects_blank_and_duplicate_names_ignoring_case(): void
    {
        Participant::factory()->create(['name' => 'Dewi Lestari']);

        Livewire::test(ParticipantForm::class)
            ->set('name', ' ')->call('save')->assertHasErrors(['name' => 'required'])
            ->set('name', 'DEWI lestari')->call('save')->assertHasErrors('name');

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_participant_can_be_edited_and_may_keep_its_own_name(): void
    {
        $participant = Participant::factory()->create(['name' => 'Lama']);

        Livewire::test(ParticipantForm::class)
            ->dispatch('open-participant-form', id: $participant->id)
            ->assertSet('name', 'Lama')
            ->call('save')
            ->assertHasNoErrors()
            ->set('name', 'Baru')
            ->call('save')
            ->assertRedirect(route('doorprize'));

        $this->assertSame('Baru', $participant->fresh()->name);
    }

    public function test_participant_can_be_deleted(): void
    {
        $participant = Participant::factory()->create();

        $this->delete(route('doorprize.participants.destroy', $participant))->assertRedirect(route('doorprize'));

        $this->assertModelMissing($participant);
    }

    public function test_list_searches_sorts_and_loads_more(): void
    {
        Participant::factory()->count(12)->sequence(fn ($s) => ['name' => 'Tamu '.str_pad($s->index, 2, '0', STR_PAD_LEFT)])->create();
        Participant::factory()->create(['name' => 'Zainal']);

        Livewire::test(ParticipantList::class)
            ->assertViewHas('participants', fn ($p) => $p->count() === 8)
            ->assertViewHas('hasMore', true)
            ->call('loadMore')
            ->assertViewHas('participants', fn ($p) => $p->count() === 13)
            ->set('search', 'zain')
            ->assertViewHas('total', 1)
            ->set('search', '')
            ->set('sort', 'name:desc')
            ->assertViewHas('participants', fn ($p) => $p->first()->name === 'Zainal');
    }

    public function test_manual_add_saves_category(): void
    {
        Livewire::test(ParticipantForm::class)
            ->dispatch('open-participant-form', id: null, category: 'Keluarga CPW')
            ->assertSet('category', 'Keluarga CPW')
            ->set('name', 'Paman Budi')
            ->call('save')
            ->assertRedirect(route('doorprize'));

        $this->assertDatabaseHas('participants', [
            'name' => 'Paman Budi',
            'category' => 'Keluarga CPW',
        ]);
    }

    public function test_import_with_category_column(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'test');
        $writer = new \OpenSpout\Writer\XLSX\Writer;
        $writer->openToFile($path);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Nama Peserta', 'Kategori']));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Siti Aisyah', 'Teman CPP']));
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Joko Anwar', 'Teman CPW']));
        $writer->close();

        $content = file_get_contents($path);
        unlink($path);
        $file = UploadedFile::fake()->createWithContent('peserta.xlsx', $content);

        Livewire::test(ParticipantImport::class)
            ->set('file', $file)
            ->call('save')
            ->assertRedirect(route('doorprize'));

        $this->assertDatabaseHas('participants', ['name' => 'Siti Aisyah', 'category' => 'Teman CPP']);
        $this->assertDatabaseHas('participants', ['name' => 'Joko Anwar', 'category' => 'Teman CPW']);
    }

    public function test_import_with_default_category_fallback(): void
    {
        $file = $this->xlsx(['Hendra Gunawan', 'Maya Indah']);

        Livewire::test(ParticipantImport::class)
            ->set('category', 'Keluarga CPP')
            ->set('file', $file)
            ->call('save')
            ->assertRedirect(route('doorprize'));

        $this->assertDatabaseHas('participants', ['name' => 'Hendra Gunawan', 'category' => 'Keluarga CPP']);
        $this->assertDatabaseHas('participants', ['name' => 'Maya Indah', 'category' => 'Keluarga CPP']);
    }
}

