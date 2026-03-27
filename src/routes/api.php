<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    \App\Http\Middleware\RestrictPosIpAddress::class,
    \App\Http\Middleware\AuthenticateStoreApiKey::class,
])->prefix('stores/{store}')->group(function () {
    // 書籍単価照会（機能3）
    Route::get('/books/{jan_code}', [\App\Http\Controllers\Api\BookController::class, 'show'])
        ->where('jan_code', '[0-9]{26}')
        ->name('api.stores.books.show');
});
