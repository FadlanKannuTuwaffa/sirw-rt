<?php

use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'assistant.thread'])->group(function () {
    Route::post('/assistant/chat', [AssistantController::class, 'chat']);
    Route::post('/assistant/feedback/kb', [AssistantController::class, 'submitKnowledgeFeedback']);
    Route::post('/assistant/feedback/interactions', [AssistantController::class, 'submitInteractionFeedback']);
    Route::get('/assistant/chat', function () {
        return response()->json(['error' => 'Use POST method'], 405);
    });
});
