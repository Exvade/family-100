<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(): View
    {
        $questions = Question::withCount('answers')->orderBy('id')->get();

        return view('family-100.questions.index', compact('questions'));
    }

    public function create(): View
    {
        return view('family-100.questions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $question = Question::create($this->validated($request));

        return redirect()
            ->route('family-100.answers.index', $question)
            ->with('status', 'Pertanyaan ditambahkan. Silakan tambahkan jawabannya.');
    }

    public function edit(Question $question): View
    {
        return view('family-100.questions.edit', compact('question'));
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $question->update($this->validated($request));

        return redirect()
            ->route('family-100.questions.index')
            ->with('status', 'Pertanyaan diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()
            ->route('family-100.questions.index')
            ->with('status', 'Pertanyaan dihapus.');
    }

    public function tv(Question $question): View
    {
        $answers = $question->answers()->limit($question->display_limit)->get();

        return view('family-100.tv', [
            'question' => $question,
            'answers' => $answers,
            'total' => $question->answers()->count(),
            'timer' => $question->timerState(),
        ]);
    }

    public function tvState(Question $question): JsonResponse
    {
        return response()->json([
            'answered' => $question->answers()
                ->limit($question->display_limit)
                ->get()
                ->where('is_answered', true)
                ->pluck('id')
                ->values(),
            'timer' => $question->timerState(),
            'wrong_count' => $question->wrong_count,
        ]);
    }

    /** Operator menekan "Salah": TV menampilkan X setiap kali penghitung ini bertambah. */
    public function wrong(Question $question): JsonResponse
    {
        $question->increment('wrong_count');

        return response()->json(['wrong_count' => $question->wrong_count]);
    }

    public function timer(Question $question, string $action): JsonResponse
    {
        match ($action) {
            'start' => $question->startTimer(),
            'pause' => $question->pauseTimer(),
            'reset' => $question->resetTimer(),
        };

        return response()->json($question->timerState());
    }

    /** @return array{question: string, display_limit: int} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'display_limit' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }
}
