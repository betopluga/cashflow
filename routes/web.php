<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\CategoryController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::resource('categories', CategoryController::class)
    ->middleware(['auth', 'verified']);

Route::get('transactions', function () {
    return Inertia::render('Transactions');
})->middleware(['auth', 'verified'])->name('transactions');

require __DIR__.'/settings.php';
