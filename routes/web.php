<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SummarizerController;

Route::get('/', [SummarizerController::class, 'index']);

Route::post('/summarize', [SummarizerController::class, 'summarize'])->name('summarize');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
