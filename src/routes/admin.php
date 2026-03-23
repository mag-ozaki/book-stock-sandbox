<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\StoreUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    // ゲスト向け認証ルート
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
    });

    // 認証済み admin 向けルート
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/dashboard', fn () => inertia('Admin/Dashboard'))->name('dashboard');

        Route::resource('stores', StoreController::class)
            ->except(['show']);

        Route::resource('store-users', StoreUserController::class)
            ->except(['show'])
            ->parameters(['store-users' => 'store_user']);
    });
});
