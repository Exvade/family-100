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

    public function test_template_downloads_as_an_xlsx_with_the_header_in_the_first_sheet(): void
    {
        $response = $this->get(route('doorprize.template'))->assertOk();
        $this->assertStringContainsString('template-peserta-doorprize.xlsx', $response->headers->get('content-disposition'));

        $path = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($path, $response->streamedContent());

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

        $this->assertSame(['Nama Peserta'], $sheets['Peserta']);
        $this->assertArrayHasKey('Petunjuk', $sheets);
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
        // MAX_ROWS peserta + 1 baris judul masih boleh; satu baris lagi ditolak.
        $names = array_map(fn ($i) => "Peserta $i", range(1, ParticipantSpreadsheet::MAX_ROWS + 2));

        Livewire::test(ParticipantImport::class)
            ->set('file', $this->xlsx($names))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertDatabaseCount('participants', 0);   // tidak ada yang masuk setengah jalan
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
}
