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

    #[On('open-participant-form')]
    public function open(?int $id = null): void
    {
        $this->resetValidation();

        if ($id !== null) {
            $participant = Participant::findOrFail($id);
            $this->participantId = $participant->id;
            $this->name = $participant->name;
        } else {
            $this->participantId = null;
            $this->name = '';
        }

        $this->dispatch('participant-form-ready');
    }

    public function save(): void
    {
        $this->name = trim(preg_replace('/\s+/u', ' ', $this->name));

        $this->validate(['name' => ['required', 'string', 'max:255']], [
            'name.required' => 'Nama peserta wajib diisi.',
            'name.max' => 'Nama peserta maksimal 255 karakter.',
        ]);

        if (Participant::nameTaken($this->name, $this->participantId)) {
            $this->addError('name', 'Peserta dengan nama ini sudah ada.');

            return;
        }

        if ($this->participantId !== null) {
            Participant::findOrFail($this->participantId)->update(['name' => $this->name]);
            $message = 'Peserta diperbarui.';
        } else {
            Participant::create(['name' => $this->name]);
            $message = 'Peserta ditambahkan.';
        }

        session()->flash('status', $message);

        $this->redirect(route('doorprize'));
    }

    public function render(): View
    {
        return view('livewire.participant-form');
    }
}
