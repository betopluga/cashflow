<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('categories', CategoryController::class)
    ->middleware(['auth', 'verified']);

Route::get('transactions/totals', [TransactionController::class, 'totals'])
    ->middleware(['auth', 'verified']);

Route::get('transactions/years', [TransactionController::class, 'years'])
    ->middleware(['auth', 'verified']);

Route::get('transactions/graph', [TransactionController::class, 'graphByYear'])
    ->middleware(['auth', 'verified']);

Route::get('transactions/category-breakdown', [TransactionController::class, 'categoryBreakdown'])
    ->middleware(['auth', 'verified']);

Route::resource('transactions', TransactionController::class)
    ->middleware(['auth', 'verified']);

require __DIR__.'/settings.php';
