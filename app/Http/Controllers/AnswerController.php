<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnswerController extends Controller
{
    public function index(Question $question): View
    {
        $answers = $question->answers()->get();
        $onTvIds = $answers->take($question->display_limit)->pluck('id');

        return view('family-100.answers.index', compact('question', 'answers', 'onTvIds') + ['timer' => $question->timerState()]);
    }

    public function create(Question $question): View
    {
        $nextRanking = ($question->answers()->max('ranking') ?? 0) + 1;

        return view('family-100.answers.create', compact('question', 'nextRanking'));
    }

    public function store(Request $request, Question $question): RedirectResponse
    {
        $question->answers()->create($this->validated($request));

        return redirect()
            ->route('family-100.answers.index', $question)
            ->with('status', 'Jawaban ditambahkan.');
    }

    public function edit(Answer $answer): View
    {
        $question = $answer->question;

        return view('family-100.answers.edit', compact('question', 'answer'));
    }

    public function update(Request $request, Answer $answer): RedirectResponse
    {
        $answer->update($this->validated($request));

        return redirect()
            ->route('family-100.answers.index', $answer->question_id)
            ->with('status', 'Jawaban diperbarui.');
    }

    public function destroy(Answer $answer): RedirectResponse
    {
        $answer->delete();

        return redirect()
            ->route('family-100.answers.index', $answer->question_id)
            ->with('status', 'Jawaban dihapus.');
    }

    /** Tandai jawaban sudah/belum terjawab; hanya jawaban yang masuk batas tampil di TV yang bisa. */
    public function toggleAnswered(Answer $answer): JsonResponse
    {
        $question = $answer->question;
        $onTv = $question->answers()->limit($question->display_limit)->pluck('id')->contains($answer->id);

        abort_unless($onTv, 422, 'Jawaban ini di luar batas jawaban yang tampil di TV.');

        $answer->is_answered = ! $answer->is_answered;
        $answer->save();

        return response()->json(['answered' => $answer->is_answered]);
    }

    /** @return array{answer: string, ranking: int} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'answer' => ['required', 'string', 'max:255'],
            'ranking' => ['required', 'integer', 'min:1'],
        ]);
    }
}
