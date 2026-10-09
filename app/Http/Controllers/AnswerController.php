<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Gift;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AnswerController extends Controller
{
    public function index(Question $question): View
    {
        $activeId = Setting::get(Setting::ACTIVE_QUESTION);
        $isSwitching = ($activeId != $question->id);

        if ($isSwitching) {
            // Ketika berpindah pertanyaan, tutup semua jawaban dan reset hitungan salah
            $question->answers()->update(['is_answered' => false]);
            $question->wrong_count = 0;
            $question->save();
            $question->resetTimer();
            Setting::put(Setting::ACTIVE_QUESTION, $question->id);
        }

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

        $giftCount = Setting::giftCount();
        Gift::ensureCount($giftCount);
        $gifts = Gift::where('number', '<=', $giftCount)->orderBy('number')->get();

        return view('family-100.answers.index', compact('question', 'answers', 'onTvIds') + [
            'timer' => $question->timerState(),
            'prevQuestion' => $prevQuestion,
            'nextQuestion' => $nextQuestion,
            'currentNumber' => $currentNumber,
            'totalQuestions' => $totalQuestions,
            'allQuestions' => $allQuestions,
            'tvMode' => Setting::tvMode(),
            'giftCount' => $giftCount,
            'gifts' => $gifts,
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
