<?php

use App\Http\Controllers\AnswerController;
use App\Http\Controllers\DoorprizeController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('family-100')->name('family-100.')->group(function () {
    Route::resource('pertanyaan', QuestionController::class)
        ->parameters(['pertanyaan' => 'question'])
        ->except('show')
        ->names('questions');
    Route::get('tv', [QuestionController::class, 'universalTv'])->name('tv');
    Route::get('tv/state', [QuestionController::class, 'universalTvState'])->name('tv.state');
    Route::get('pertanyaan/{question}/tv', [QuestionController::class, 'tv'])->name('questions.tv');

    Route::get('pertanyaan/{question}/tv/state', [QuestionController::class, 'tvState'])->name('questions.tv.state');
    Route::post('pertanyaan/{question}/salah', [QuestionController::class, 'wrong'])->middleware('throttle:wrong-answer')->name('questions.wrong');
    Route::post('pertanyaan/{question}/reset', [QuestionController::class, 'resetRound'])->name('questions.reset');
    Route::post('pertanyaan/{question}/timer/{action}', [QuestionController::class, 'timer'])
        ->whereIn('action', ['start', 'pause', 'reset'])
        ->name('questions.timer');
    Route::patch('jawaban/{answer}/terjawab', [AnswerController::class, 'toggleAnswered'])->name('answers.answered');

    Route::resource('pertanyaan.jawaban', AnswerController::class)
        ->parameters(['pertanyaan' => 'question', 'jawaban' => 'answer'])
        ->only('index', 'destroy')
        ->shallow()
        ->names('answers');

    // Dashboard & Kontrol Hadiah
    Route::get('hadiah', [GiftController::class, 'index'])->name('gifts.index');
    Route::put('hadiah/{gift}', [GiftController::class, 'update'])->name('gifts.update');
    Route::post('hadiah/{gift}/toggle', [GiftController::class, 'toggle'])->name('gifts.toggle');
    Route::post('hadiah/{gift}/open', [GiftController::class, 'open'])->name('gifts.open');
    Route::post('hadiah/reset', [GiftController::class, 'reset'])->name('gifts.reset');
    Route::post('hadiah/bulk', [GiftController::class, 'bulkUpdate'])->name('gifts.bulk');
    Route::post('hadiah/acak', [GiftController::class, 'shuffle'])->name('gifts.shuffle');
    Route::post('hadiah/jumlah', [GiftController::class, 'updateCount'])->name('gifts.count');
    Route::post('tv/mode', [GiftController::class, 'switchTvMode'])->name('tv.mode');

    // Layar TV Hadiah Panggung
    Route::get('hadiah/tv', [GiftController::class, 'tv'])->name('gifts.tv');
    Route::get('hadiah/tv/state', [GiftController::class, 'tvState'])->name('gifts.tv.state');
});

Route::get('family-100/pengaturan', [SettingController::class, 'edit'])->name('family-100.settings.edit');
Route::put('family-100/pengaturan', [SettingController::class, 'update'])->name('family-100.settings.update');

Route::get('/doorprize', [DoorprizeController::class, 'index'])->name('doorprize');
Route::get('/doorprize/template', [DoorprizeController::class, 'template'])->name('doorprize.template');
Route::get('/doorprize/winners/export', [DoorprizeController::class, 'exportWinners'])->name('doorprize.winners.export');
Route::delete('/doorprize/peserta/{participant}', [DoorprizeController::class, 'destroy'])->name('doorprize.participants.destroy');
Route::get('/doorprize/tv', [DoorprizeController::class, 'tv'])->name('doorprize.tv');
Route::get('/doorprize/tv/state', [DoorprizeController::class, 'tvState'])->name('doorprize.tv.state');
Route::post('/doorprize/spin/start', [DoorprizeController::class, 'start'])->name('doorprize.spin.start');
Route::post('/doorprize/spin/stop', [DoorprizeController::class, 'stop'])->name('doorprize.spin.stop');
Route::post('/doorprize/spin/reset', [DoorprizeController::class, 'reset'])->name('doorprize.spin.reset');
Route::post('/doorprize/spin/duration', [DoorprizeController::class, 'duration'])->name('doorprize.spin.duration');
Route::post('/doorprize/spin/configure', [DoorprizeController::class, 'configure'])->name('doorprize.spin.configure');
Route::post('/doorprize/setting', [DoorprizeController::class, 'saveSetting'])->name('doorprize.setting');
