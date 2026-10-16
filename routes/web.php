<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegenerateController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('conversations.index');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('/conversations/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');

    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->name('messages.store')
        ->middleware('throttle:chat');

    Route::post('/conversations/{conversation}/regenerate', [RegenerateController::class, 'store'])
        ->name('messages.regenerate')
        ->middleware('throttle:chat');

    Route::post('/conversations/{conversation}/stream', [StreamController::class, 'stream'])
        ->name('messages.stream')
        ->middleware('throttle:chat');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
