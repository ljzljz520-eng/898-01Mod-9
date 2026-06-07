<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->middleware('auth:sanctum');

    // Verification routes
    Route::post('/verify/apply', [App\Http\Controllers\Api\AuthController::class, 'applyVerification'])->middleware('auth:sanctum');
    Route::post('/verify/cancel', [App\Http\Controllers\Api\AuthController::class, 'cancelVerification'])->middleware('auth:sanctum');
    Route::post('/verify/{user}/review', [App\Http\Controllers\Api\AuthController::class, 'reviewVerification'])->middleware('auth:sanctum');

    // Move out route
    Route::post('/move-out', [App\Http\Controllers\Api\AuthController::class, 'moveOut'])->middleware('auth:sanctum');
});

// Buildings routes
Route::apiResource('buildings', App\Http\Controllers\Api\BuildingController::class)->only(['index', 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('buildings', App\Http\Controllers\Api\BuildingController::class)->only(['store', 'update', 'destroy']);
});

// Topics routes
Route::get('topics', [App\Http\Controllers\Api\TopicController::class, 'index'])->middleware('circle.access');
Route::get('topics/{topic}', [App\Http\Controllers\Api\TopicController::class, 'show'])->middleware('circle.access');
Route::post('topics', [App\Http\Controllers\Api\TopicController::class, 'store'])->middleware(['auth:sanctum', 'circle.access']);
Route::put('topics/{topic}', [App\Http\Controllers\Api\TopicController::class, 'update'])->middleware(['auth:sanctum', 'circle.access']);
Route::patch('topics/{topic}', [App\Http\Controllers\Api\TopicController::class, 'update'])->middleware(['auth:sanctum', 'circle.access']);
Route::delete('topics/{topic}', [App\Http\Controllers\Api\TopicController::class, 'destroy'])->middleware(['auth:sanctum', 'circle.access']);

// Topic replies routes
Route::get('topics/{topic}/replies', [App\Http\Controllers\Api\ReplyController::class, 'index'])->middleware('circle.access');
Route::post('topics/{topic}/replies', [App\Http\Controllers\Api\ReplyController::class, 'store'])->middleware(['auth:sanctum', 'circle.access']);

// Replies routes (for update/delete)
Route::apiResource('replies', App\Http\Controllers\Api\ReplyController::class)->except(['index', 'store'])->middleware(['auth:sanctum', 'circle.access']);

// Knowledge cards routes
Route::get('knowledge-cards/categories', [App\Http\Controllers\Api\KnowledgeCardController::class, 'categories']);
Route::get('knowledge-cards/active', [App\Http\Controllers\Api\KnowledgeCardController::class, 'active']);
Route::get('knowledge-cards/search', [App\Http\Controllers\Api\KnowledgeCardController::class, 'searchWithPriority']);
Route::apiResource('knowledge-cards', App\Http\Controllers\Api\KnowledgeCardController::class)->except(['store', 'update', 'destroy']);
Route::post('knowledge-cards', [App\Http\Controllers\Api\KnowledgeCardController::class, 'store'])->middleware('auth:sanctum');
Route::put('knowledge-cards/{knowledgeCard}', [App\Http\Controllers\Api\KnowledgeCardController::class, 'update'])->middleware('auth:sanctum');
Route::patch('knowledge-cards/{knowledgeCard}', [App\Http\Controllers\Api\KnowledgeCardController::class, 'update'])->middleware('auth:sanctum');
Route::delete('knowledge-cards/{knowledgeCard}', [App\Http\Controllers\Api\KnowledgeCardController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('knowledge-cards/{knowledgeCard}/review', [App\Http\Controllers\Api\KnowledgeCardController::class, 'markReviewed'])->middleware('auth:sanctum');
Route::get('knowledge-cards-review', [App\Http\Controllers\Api\KnowledgeCardController::class, 'needsReviewList'])->middleware('auth:sanctum');
