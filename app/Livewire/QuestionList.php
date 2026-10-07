<?php

namespace App\Livewire;

use App\Livewire\Concerns\LoadsMore;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class QuestionList extends Component
{
    use LoadsMore;

    public function render(): View
    {
        [$key, $dir] = $this->sortParts(['no', 'question', 'answers', 'limit']);
        $column = ['no' => 'id', 'question' => 'question', 'answers' => 'answers_count', 'limit' => 'display_limit'][$key];

        $query = Question::withCount('answers');
        $this->applySearch($query, 'question');

        $total = (clone $query)->count();
        $query->orderBy($column, $dir);
        if ($column !== 'id') {
            $query->orderBy('id', $dir);
        }

        $rows = $query->limit($this->limit())->get();

        return view('livewire.question-list', [
            'questions' => $rows,
            'total' => $total,
            'hasMore' => $total > $rows->count(),
            // Nomor mengikuti urutan awal (id), tetap sama walau daftar diurutkan atau dicari.
            'numbers' => Question::orderBy('id')->pluck('id')->flip(),
            'sortOptions' => [
                'no:asc' => 'Nomor (terkecil)',
                'no:desc' => 'Nomor (terbesar)',
                'question:asc' => 'Pertanyaan (A–Z)',
                'question:desc' => 'Pertanyaan (Z–A)',
                'answers:desc' => 'Jumlah jawaban (terbanyak)',
                'answers:asc' => 'Jumlah jawaban (tersedikit)',
                'limit:desc' => 'Tampil di TV (terbanyak)',
                'limit:asc' => 'Tampil di TV (tersedikit)',
            ],
        ]);
    }
}
