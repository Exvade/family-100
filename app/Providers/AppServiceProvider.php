<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tombol "Salah" mengizinkan host menekan responsif selama permainan berlangsung
        RateLimiter::for('wrong-answer', function (Request $request) {
            $question = $request->route('question');
            $id = $question instanceof Model ? $question->getKey() : $question;

            return Limit::perMinute(120)->by('wrong-answer:'.$id);
        });
    }
}
