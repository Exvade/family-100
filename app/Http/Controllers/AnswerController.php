<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnswerController extends Controller
{
    public function index(Question $question): View
    {
        Setting::put(Setting::ACTIVE_QUESTION, $question->id);

        $answers = $question->answers()->get();
        $onTvIds = $answers->take($question->display_limit)->pluck('id');

        $allQuestions = Question::orderBy('id')->get(['id', 'question']);
        $currentIndex = $allQuestions->search(fn ($q) => $q->id === $question->id);

        $prevQuestion = ($currentIndex !== false && $currentIndex > 0)
            ? $allQuestions->get($currentIndex - 1)
            : null;

        $nextQuestion = ($currentIndex !== false && $currentIndex < $allQuestions->count() - 1)
            ? $allQuestions->get($currentIndex + 1)
            : null;

        $currentNumber = ($currentIndex !== false ? $currentIndex + 1 : 1);
        $totalQuestions = $allQuestions->count();

        return view('family-100.answers.index', compact('question', 'answers', 'onTvIds') + [
            'timer' => $question->timerState(),
            'prevQuestion' => $prevQuestion,
            'nextQuestion' => $nextQuestion,
            'currentNumber' => $currentNumber,
            'totalQuestions' => $totalQuestions,
            'allQuestions' => $allQuestions,
        ]);
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
}
