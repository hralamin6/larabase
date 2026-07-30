<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::view('app', 'app::dashboard')->name('dashboard');
    Route::livewire('app/profile', 'app::profile')->name('profile.edit');
    Route::livewire('app/profile#appearance', 'app::profile')->name('appearance.edit');
    Route::livewire('app/profile#security', 'app::profile')->name('security.edit');
    Route::livewire('app/roles-permissions', 'app::settings.roles')->name('roles.index');
   
});




Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
