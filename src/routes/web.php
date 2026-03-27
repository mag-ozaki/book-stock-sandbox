<?php

use App\Http\Controllers\Web\BookController;
use App\Http\Controllers\Web\PurchaseHistoryController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\StoreUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => inertia('Dashboard'))->name('dashboard');

    Route::resource('store-users', StoreUserController::class)
        ->except(['show'])
        ->parameters(['store-users' => 'store_user']);

    Route::resource('books', BookController::class)
        ->except(['show']);

    Route::get('/stocks/export', [StockController::class, 'export'])->name('stocks.export');
    Route::resource('stocks', StockController::class)
        ->except(['show']);

    Route::resource('purchase-histories', PurchaseHistoryController::class)
        ->except(['edit', 'update'])
        ->parameters(['purchase-histories' => 'purchase_history']);

    Route::resource('sale-histories', \App\Http\Controllers\Web\SaleHistoryController::class)
        ->only(['index', 'show'])
        ->parameters(['sale-histories' => 'sale_history']);
});
