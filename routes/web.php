<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.book-appointment');

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::view('/doctorShift', 'pages.doctor-schedule')->name('dashboard.DoctorShift');
    Route::view('/manage-appointments', 'pages.manage-appointments')->name('dashboard.manage-appointments');
    Route::view('/today-appointments', 'pages.today-appointments')->name('dashboard.today-appointments');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
