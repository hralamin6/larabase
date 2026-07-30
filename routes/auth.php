<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'auth::login')->name('login');
    Route::livewire('register', 'auth::register')->name('register');
    Route::livewire('forgot-password', 'auth::forgot-password')->name('password.request');
    Route::livewire('reset-password/{token}', 'auth::reset-password')->name('password.reset');
    Route::livewire('two-factor-challenge', 'auth::two-factor-challenge')->name('two-factor.login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('verify-email', 'auth::verify-email')->name('verification.notice');
    Route::livewire('confirm-password', 'auth::confirm-password')->name('password.confirm');
});
