<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QuoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key', 'throttle:60,1'])->group(function (): void {
    Route::get('products', [ProductController::class, 'index'])->name('api.products.index');
    Route::post('quotes/calculate', [QuoteController::class, 'calculate'])->name('api.quotes.calculate');
    Route::post('quotes', [QuoteController::class, 'store'])->name('api.quotes.store');
});
