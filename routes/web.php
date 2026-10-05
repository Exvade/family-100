<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('family-100')->name('family-100.')->group(function () {
    Route::resource('pertanyaan', QuestionController::class)
        ->parameters(['pertanyaan' => 'question'])
        ->except('show')
        ->names('questions');
    Route::get('pertanyaan/{question}/tv', [QuestionController::class, 'tv'])->name('questions.tv');

    Route::get('pertanyaan/{question}/tv/state', [QuestionController::class, 'tvState'])->name('questions.tv.state');
    Route::post('pertanyaan/{question}/salah', [QuestionController::class, 'wrong'])->middleware('throttle:wrong-answer')->name('questions.wrong');
    Route::post('pertanyaan/{question}/timer/{action}', [QuestionController::class, 'timer'])
        ->whereIn('action', ['start', 'pause', 'reset'])
        ->name('questions.timer');
    Route::patch('jawaban/{answer}/terjawab', [AnswerController::class, 'toggleAnswered'])->name('answers.answered');

    Route::resource('pertanyaan.jawaban', AnswerController::class)
        ->parameters(['pertanyaan' => 'question', 'jawaban' => 'answer'])
        ->except('show')
        ->shallow()
        ->names('answers');
});

Route::get('family-100/pengaturan', [SettingController::class, 'edit'])->name('family-100.settings.edit');
Route::put('family-100/pengaturan', [SettingController::class, 'update'])->name('family-100.settings.update');

Route::view('/doorprize', 'doorprize')->name('doorprize');
