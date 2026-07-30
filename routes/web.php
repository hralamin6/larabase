<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'web::welcome')->name('home');


require __DIR__.'/auth.php';
require __DIR__.'/app.php';
