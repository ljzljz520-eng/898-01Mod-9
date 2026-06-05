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
