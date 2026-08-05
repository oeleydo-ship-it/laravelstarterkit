<?php

use App\Http\Controllers\Api\ChatConversationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat REST API
|--------------------------------------------------------------------------
|
| Token-authenticated, tenant-scoped. Authenticate with:
|
|     Authorization: Bearer chat_xxxxxxxx
|
| Tokens are issued per workspace under Chat → Settings.
|
*/

Route::prefix('chat')
    ->middleware([\App\Http\Middleware\AuthenticateChatApi::class, 'throttle:120,1'])
    ->name('api.chat.')
    ->group(function () {
        Route::get('conversations', [ChatConversationController::class, 'index'])->name('conversations.index');
        Route::get('conversations/{conversation}', [ChatConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversation}/messages', [ChatConversationController::class, 'storeMessage'])->name('conversations.messages.store');
        Route::post('conversations/{conversation}/close', [ChatConversationController::class, 'close'])->name('conversations.close');
    });
