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
        // Tombol "Salah" hanya boleh sekali per 5 detik untuk satu pertanyaan, dari siapa pun yang menekan.
        RateLimiter::for('wrong-answer', function (Request $request) {
            // Throttle berjalan sebelum route model binding, jadi parameter bisa berupa ID atau model.
            $question = $request->route('question');
            $id = $question instanceof Model ? $question->getKey() : $question;

            return Limit::perSecond(1, 5)->by('wrong-answer:'.$id);
        });
    }
}
