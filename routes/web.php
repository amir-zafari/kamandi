<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::view('/1', 'pages.doctor-schedule')->name('dashboard.users');

});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
