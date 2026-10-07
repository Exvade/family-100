<?php

namespace App\Livewire;

use App\Models\Participant;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Modal tambah/edit peserta; dibuka lewat event "open-participant-form" (id kosong = peserta baru). */
class ParticipantForm extends Component
{
    #[Locked]
    public ?int $participantId = null;

    public string $name = '';

    public string $category = Participant::DEFAULT_CATEGORY;

    #[On('open-participant-form')]
    public function open(?int $id = null, ?string $category = null): void
    {
        $this->resetValidation();

        if ($id !== null) {
            $participant = Participant::findOrFail($id);
            $this->participantId = $participant->id;
            $this->name = $participant->name;
            $this->category = $participant->category;
        } else {
            $this->participantId = null;
            $this->name = '';
            $this->category = $category ? Participant::canonicalCategory($category) : Participant::DEFAULT_CATEGORY;
        }

        $this->dispatch('participant-form-ready');
    }

    public function save(): void
    {
        $this->name = trim(preg_replace('/\s+/u', ' ', $this->name));
        $this->category = Participant::canonicalCategory($this->category);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
        ], [
            'name.required' => 'Nama peserta wajib diisi.',
            'name.max' => 'Nama peserta maksimal 255 karakter.',
            'category.required' => 'Kategori peserta wajib dipilih.',
        ]);

        if (Participant::nameTaken($this->name, $this->participantId)) {
            $this->addError('name', 'Peserta dengan nama ini sudah ada.');

            return;
        }

        if ($this->participantId !== null) {
            Participant::findOrFail($this->participantId)->update([
                'name' => $this->name,
                'category' => $this->category,
            ]);
            $message = 'Peserta diperbarui.';
        } else {
            Participant::create([
                'name' => $this->name,
                'category' => $this->category,
            ]);
            $message = 'Peserta ditambahkan.';
        }

        session()->flash('status', $message);

        $this->redirect(route('doorprize'));
    }

    public function render(): View
    {
        return view('livewire.participant-form', [
            'categories' => Participant::CATEGORIES,
        ]);
    }
}
