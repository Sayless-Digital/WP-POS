<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// POS Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/pos', \App\Livewire\Pos\PosTerminal::class)->name('pos.terminal');
    Route::get('/pos/checkout', \App\Livewire\Pos\Checkout::class)->name('pos.checkout');
});

require __DIR__.'/auth.php';
