<?php

use Illuminate\Support\Facades\Route;

// 首页重定向到主题列表
Route::get('/', function () {
    return redirect()->route('topics.index');
});

// 前台认证路由
Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegisterForm'])->name('register')->middleware('guest');
Route::post('/register', [App\Http\Controllers\AuthController::class, 'register']);
Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout')->middleware('auth');

// 主题路由
Route::resource('topics', App\Http\Controllers\TopicController::class);
Route::post('topics/{topic}/replies', [App\Http\Controllers\ReplyController::class, 'store'])->name('replies.store')->middleware('auth');
Route::delete('replies/{reply}', [App\Http\Controllers\ReplyController::class, 'destroy'])->name('replies.destroy')->middleware('auth');

// 知识卡片路由
Route::resource('knowledge-cards', App\Http\Controllers\KnowledgeCardController::class);
Route::post('knowledge-cards/{knowledgeCard}/review', [App\Http\Controllers\KnowledgeCardController::class, 'review'])->name('knowledge-cards.review')->middleware('auth');
Route::get('knowledge-cards-review', [App\Http\Controllers\KnowledgeCardController::class, 'reviewList'])->name('knowledge-cards.review-list')->middleware('auth');

// 用户资料路由
Route::get('/profile', [App\Http\Controllers\AuthController::class, 'profile'])->name('profile')->middleware('auth');

// 认证相关路由
Route::get('/verify-apply', [App\Http\Controllers\AuthController::class, 'showVerificationForm'])->name('verify.apply')->middleware('auth');
Route::post('/verify-apply', [App\Http\Controllers\AuthController::class, 'applyVerification'])->middleware('auth');
Route::get('/verification-list', [App\Http\Controllers\AuthController::class, 'verificationList'])->name('verification.list')->middleware('auth');
Route::post('/verification-review/{user}', [App\Http\Controllers\AuthController::class, 'reviewVerification'])->name('verification.review')->middleware('auth');
Route::get('/move-out', [App\Http\Controllers\AuthController::class, 'showMoveOutForm'])->name('move.out')->middleware('auth');
Route::post('/move-out', [App\Http\Controllers\AuthController::class, 'moveOut'])->middleware('auth');
Route::post('/cancel-verification', [App\Http\Controllers\AuthController::class, 'cancelVerification'])->name('verification.cancel')->middleware('auth');

// 楼栋路由
Route::resource('buildings', App\Http\Controllers\BuildingController::class);
