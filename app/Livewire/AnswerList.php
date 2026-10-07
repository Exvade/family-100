<?php

namespace App\Livewire;

use App\Livewire\Concerns\LoadsMore;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AnswerList extends Component
{
    use LoadsMore;

    #[Locked]
    public int $questionId;

    public function mount(Question $question): void
    {
        $this->questionId = $question->id;
    }

    public function render(): View
    {
        $question = Question::findOrFail($this->questionId);
        [$key, $dir] = $this->sortParts(['no', 'name']);

        $query = $question->answers()->reorder();
        $this->applySearch($query, 'answer');

        $total = (clone $query)->count();

        // "no" = urutan awal (ranking lalu id), sama dengan urutan ranking.
        if ($key === 'name') {
            $query->orderBy('answer', $dir)->orderBy('id', $dir);
        } else {
            $query->orderBy('ranking', $dir)->orderBy('id', $dir);
        }

        $rows = $query->limit($this->limit())->get();
        $defaultOrder = $question->answers()->pluck('id');

        return view('livewire.answer-list', [
            'question' => $question,
            'answers' => $rows,
            'total' => $total,
            'hasMore' => $total > $rows->count(),
            'numbers' => $defaultOrder->flip(),
            'onTvIds' => $defaultOrder->take($question->display_limit),
            'sortOptions' => [
                'no:asc' => 'Ranking (terkecil)',
                'no:desc' => 'Ranking (terbesar)',
                'name:asc' => 'Nama jawaban (A–Z)',
                'name:desc' => 'Nama jawaban (Z–A)',
            ],
        ]);
    }
}
