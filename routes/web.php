<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculatorController::class, 'index'])->name('calculator');
Route::post('/calculator/price', [CalculatorController::class, 'price'])
    ->middleware('throttle:120,1')
    ->name('calculator.price');

Route::post('/quotes', [QuoteController::class, 'store'])->middleware('throttle:10,1')->name('quotes.store');
Route::middleware('signed')->group(function (): void {
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
});
