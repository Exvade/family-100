<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory;

    protected $fillable = ['question', 'display_limit'];

    protected function casts(): array
    {
        return ['timer_ends_at_ms' => 'integer', 'timer_remaining_ms' => 'integer'];
    }

    /**
     * Status timer: idle (belum jalan), running, paused, atau finished.
     *
     * @return array{status: string, remaining_ms: int, duration_ms: int}
     */
    public function timerState(): array
    {
        $duration = Setting::timerDuration() * 1000;

        if ($this->timer_ends_at_ms !== null) {
            $remaining = max(0, $this->timer_ends_at_ms - now()->getTimestampMs());
            $status = $remaining === 0 ? 'finished' : 'running';
        } elseif ($this->timer_remaining_ms !== null) {
            $remaining = min($this->timer_remaining_ms, $duration);
            $status = 'paused';
        } else {
            $remaining = $duration;
            $status = 'idle';
        }

        return ['status' => $status, 'remaining_ms' => $remaining, 'duration_ms' => $duration];
    }

    public function startTimer(): void
    {
        $state = $this->timerState();

        if ($state['status'] === 'running') {
            return;
        }

        $remaining = $state['status'] === 'finished' ? $state['duration_ms'] : $state['remaining_ms'];

        $this->forceFill([
            'timer_ends_at_ms' => now()->getTimestampMs() + $remaining,
            'timer_remaining_ms' => null,
        ])->save();
    }

    public function pauseTimer(): void
    {
        $state = $this->timerState();

        if ($state['status'] !== 'running') {
            return;
        }

        $this->forceFill([
            'timer_ends_at_ms' => null,
            'timer_remaining_ms' => $state['remaining_ms'],
        ])->save();
    }

    public function resetTimer(): void
    {
        $this->forceFill(['timer_ends_at_ms' => null, 'timer_remaining_ms' => null])->save();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('ranking')->orderBy('id');
    }
}
