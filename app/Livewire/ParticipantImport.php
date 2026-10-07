<?php

namespace App\Livewire;

use App\Services\ParticipantSpreadsheet;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Modal unggah peserta dari file Excel (.xlsx) atau CSV. */
class ParticipantImport extends Component
{
    use WithFileUploads;

    public $file = null;

    #[On('reset-participant-import')]
    public function resetForm(): void
    {
        $this->reset('file');
        $this->resetValidation();
    }

    public function save(ParticipantSpreadsheet $spreadsheet): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:2048'],
        ], [
            'file.required' => 'Pilih file Excel (.xlsx) atau CSV terlebih dahulu.',
            'file.mimes' => 'Format file harus .xlsx atau .csv.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
            'file.uploaded' => 'File gagal diunggah. Coba lagi.',
        ]);

        try {
            $result = $spreadsheet->import($this->file->getRealPath(), $this->file->getClientOriginalExtension());
        } catch (InvalidArgumentException $e) {
            $this->addError('file', $e->getMessage());

            return;
        }

        $parts = ["{$result['added']} peserta ditambahkan"];
        if ($result['duplicates'] > 0) {
            $parts[] = "{$result['duplicates']} dilewati karena nama sudah ada";
        }
        if ($result['invalid'] > 0) {
            $parts[] = "{$result['invalid']} baris tidak valid";
        }

        session()->flash('status', implode(', ', $parts).'.');

        $this->redirect(route('doorprize'));
    }

    public function render(): View
    {
        return view('livewire.participant-import');
    }
}
