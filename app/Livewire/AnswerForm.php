<?php

namespace App\Livewire;

use App\Models\Question;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/** Modal tambah/edit jawaban; dibuka lewat event "open-answer-form" (id kosong = jawaban baru). */
class AnswerForm extends Component
{
    #[Locked]
    public int $questionId;

    #[Locked]
    public ?int $answerId = null;

    public string $answer = '';

    public string $ranking = '1';

    public function mount(Question $question): void
    {
        $this->questionId = $question->id;
    }

    #[On('open-answer-form')]
    public function open(?int $id = null): void
    {
        $this->resetValidation();
        $question = $this->question();

        if ($id !== null) {
            // Dicari lewat pertanyaan ini, jadi id jawaban milik pertanyaan lain ditolak (404).
            $existing = $question->answers()->whereKey($id)->firstOrFail();
            $this->answerId = $existing->id;
            $this->answer = $existing->answer;
            $this->ranking = (string) $existing->ranking;
        } else {
            $this->answerId = null;
            $this->answer = '';
            $this->ranking = (string) (($question->answers()->max('ranking') ?? 0) + 1);
        }

        $this->dispatch('answer-form-ready');
    }

    public function save(): void
    {
        $data = $this->validate([
            'answer' => ['required', 'string', 'max:255'],
            'ranking' => ['required', 'integer', 'min:1'],
        ], [
            'answer.required' => 'Nama jawaban wajib diisi.',
            'answer.max' => 'Nama jawaban maksimal 255 karakter.',
            'ranking.required' => 'Ranking wajib diisi.',
            'ranking.integer' => 'Ranking harus berupa angka bulat.',
            'ranking.min' => 'Ranking minimal 1.',
        ]);

        $question = $this->question();

        if ($this->answerId !== null) {
            $question->answers()->whereKey($this->answerId)->firstOrFail()->update($data);
            $message = 'Jawaban diperbarui.';
        } else {
            $question->answers()->create($data);
            $message = 'Jawaban ditambahkan.';
        }

        session()->flash('status', $message);

        $this->redirect(route('family-100.answers.index', $question));
    }

    public function render(): View
    {
        return view('livewire.answer-form');
    }

    private function question(): Question
    {
        return Question::findOrFail($this->questionId);
    }
}
