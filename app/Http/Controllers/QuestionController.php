<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\Question;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $data = $this->validated($request);

        $answersCount = 0;
        $question = DB::transaction(function () use ($data, $request, &$answersCount) {
            $question = Question::create([
                'question' => $data['question'],
                'display_limit' => $data['display_limit'],
            ]);

            $answers = $request->input('answers', []);
            foreach ($answers as $idx => $answerData) {
                $text = trim(is_array($answerData) ? ($answerData['answer'] ?? '') : (string) $answerData);
                if ($text !== '') {
                    $ranking = is_array($answerData) && !empty($answerData['ranking'])
                        ? (int) $answerData['ranking']
                        : ($idx + 1);

                    $question->answers()->create([
                        'answer' => $text,
                        'ranking' => $ranking,
                    ]);
                    $answersCount++;
                }
            }

            return $question;
        });

        $message = $answersCount > 0
            ? "Pertanyaan dan {$answersCount} jawaban berhasil ditambahkan."
            : 'Pertanyaan berhasil ditambahkan.';

        return redirect()
            ->route('family-100.answers.index', $question)
            ->with('status', $message);
    }

    public function edit(Question $question): View
    {
        $question->load(['answers' => fn ($q) => $q->orderBy('ranking')]);

        return view('family-100.questions.edit', compact('question'));
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($question, $data, $request) {
            $question->update([
                'question' => $data['question'],
                'display_limit' => $data['display_limit'],
            ]);

            if ($request->has('answers')) {
                $submittedIds = [];
                $answers = $request->input('answers', []);

                foreach ($answers as $idx => $answerData) {
                    $text = trim(is_array($answerData) ? ($answerData['answer'] ?? '') : (string) $answerData);
                    if ($text === '') {
                        continue;
                    }

                    $ranking = is_array($answerData) && !empty($answerData['ranking'])
                        ? (int) $answerData['ranking']
                        : ($idx + 1);

                    $answerId = is_array($answerData) ? ($answerData['id'] ?? null) : null;

                    if ($answerId) {
                        $existing = $question->answers()->where('id', $answerId)->first();
                        if ($existing) {
                            $existing->update([
                                'answer' => $text,
                                'ranking' => $ranking,
                            ]);
                            $submittedIds[] = $existing->id;
                            continue;
                        }
                    }

                    $newAnswer = $question->answers()->create([
                        'answer' => $text,
                        'ranking' => $ranking,
                    ]);
                    $submittedIds[] = $newAnswer->id;
                }

                if (!empty($submittedIds)) {
                    $question->answers()->whereNotIn('id', $submittedIds)->delete();
                }
            }
        });

        return redirect()
            ->route('family-100.questions.index')
            ->with('status', 'Pertanyaan dan jawaban berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()
            ->route('family-100.questions.index')
            ->with('status', 'Pertanyaan dihapus.');
    }

    public function universalTv(): View
    {
        $activeId = Setting::get(Setting::ACTIVE_QUESTION);
        $question = ($activeId ? Question::find($activeId) : null) ?? Question::orderBy('id')->first();

        if (! $question) {
            $giftCount = Setting::giftCount();
            Gift::ensureCount($giftCount);
            $gifts = Gift::where('number', '<=', $giftCount)->orderBy('number')->get();

            return view('family-100.tv', [
                'question' => null,
                'answers' => collect(),
                'total' => 0,
                'timer' => ['status' => 'idle', 'remaining_ms' => Setting::timerDuration() * 1000, 'duration_ms' => Setting::timerDuration() * 1000],
                'tvMode' => Setting::tvMode(),
                'giftCount' => $giftCount,
                'gifts' => $gifts,
            ]);
        }

        return $this->tv($question);
    }

    public function universalTvState(): JsonResponse
    {
        $activeId = Setting::get(Setting::ACTIVE_QUESTION);
        $question = ($activeId ? Question::find($activeId) : null) ?? Question::orderBy('id')->first();

        if (! $question) {
            $giftCount = Setting::giftCount();
            $gifts = Gift::where('number', '<=', $giftCount)->orderBy('number')->get()->map(fn ($g) => [
                'id' => $g->id,
                'number' => $g->number,
                'name' => $g->name,
                'description' => $g->description,
                'is_opened' => (bool) $g->is_opened,
                'winner_name' => $g->winner_name,
            ]);

            return response()->json([
                'tv_mode' => Setting::tvMode(),
                'question_id' => null,
                'question' => null,
                'display_limit' => 0,
                'answered' => [],
                'answers' => [],
                'timer' => ['status' => 'idle', 'remaining_ms' => Setting::timerDuration() * 1000, 'duration_ms' => Setting::timerDuration() * 1000],
                'wrong_count' => 0,
                'gift_count' => $giftCount,
                'gifts' => $gifts,
            ]);
        }

        return $this->tvState($question);
    }

    public function tv(Question $question): View
    {
        Setting::put(Setting::ACTIVE_QUESTION, $question->id);

        $answers = $question->answers()->limit($question->display_limit)->get();
        $giftCount = Setting::giftCount();
        Gift::ensureCount($giftCount);
        $gifts = Gift::where('number', '<=', $giftCount)->orderBy('number')->get();

        return view('family-100.tv', [
            'question' => $question,
            'answers' => $answers,
            'total' => $question->answers()->count(),
            'timer' => $question->timerState(),
            'tvMode' => Setting::tvMode(),
            'giftCount' => $giftCount,
            'gifts' => $gifts,
        ]);
    }

    public function tvState(Question $question): JsonResponse
    {
        $answers = $question->answers()->limit($question->display_limit)->get();
        $giftCount = Setting::giftCount();
        $gifts = Gift::where('number', '<=', $giftCount)
            ->orderBy('number')
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'number' => $g->number,
                'name' => $g->name,
                'description' => $g->description,
                'is_opened' => (bool) $g->is_opened,
                'winner_name' => $g->winner_name,
            ]);

        return response()->json([
            'tv_mode' => Setting::tvMode(),
            'question_id' => $question->id,
            'question' => $question->question,
            'display_limit' => $question->display_limit,
            'answered' => $answers
                ->where('is_answered', true)
                ->pluck('id')
                ->values(),
            'answers' => $answers->map(fn ($a, $idx) => [
                'id' => $a->id,
                'rank' => $idx + 1,
                'answer' => $a->answer,
                'is_answered' => (bool) $a->is_answered,
            ])->values(),
            'timer' => $question->timerState(),
            'wrong_count' => $question->wrong_count,
            'gift_count' => $giftCount,
            'gifts' => $gifts,
        ]);
    }

    /** Operator menekan "Salah": TV menampilkan X setiap kali penghitung ini bertambah. */
    public function wrong(Question $question): JsonResponse
    {
        $question->increment('wrong_count');

        return response()->json(['wrong_count' => $question->wrong_count]);
    }

    /** Reset seluruh jawaban (tutup kembali) dan reset penghitung salah ke 0 untuk babak ini. */
    public function resetRound(Question $question): JsonResponse
    {
        $question->answers()->update(['is_answered' => false]);
        $question->wrong_count = 0;
        $question->save();
        $question->resetTimer();

        return response()->json([
            'status' => 'success',
            'wrong_count' => 0,
            'timer' => $question->timerState(),
            'message' => 'Babak berhasil direset. Semua jawaban ditutup kembali dan hitungan salah kembali ke 0.',
        ]);
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

    /** @return array{question: string, display_limit: int, answers?: array} */
    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'display_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'answers' => ['nullable', 'array'],
            'answers.*.id' => ['nullable', 'integer'],
            'answers.*.ranking' => ['nullable', 'integer'],
            'answers.*.answer' => ['nullable', 'string', 'max:255'],
        ], [
            'question.required' => 'Pertanyaan wajib diisi.',
            'display_limit.required' => 'Jumlah jawaban wajib diisi.',
            'display_limit.min' => 'Jumlah jawaban minimal 1.',
            'display_limit.max' => 'Jumlah jawaban maksimal 20.',
        ]);
    }
}
