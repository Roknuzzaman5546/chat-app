<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/chat', [ChatController::class, 'view'])->name('chat.view');
    Route::post('/chat/send', [ChatController::class, 'store']);
    Route::get('/chat/messages/{conversation}', [ChatController::class, 'index']);
    Route::get('/chat/search', [ConversationController::class, 'search']);
    Route::post('/chat/start', [ConversationController::class, 'startConversation']);
});

require __DIR__ . '/auth.php';
