<?php

use Illuminate\Support\Facades\Route;

// Named routes required by Fortify redirects and existing tests
Route::get('/', fn () => view('spa'))->name('home');
Route::get('/login', fn () => view('spa'))->middleware('guest')->name('login');
Route::get('/dashboard', fn () => view('spa'))->middleware(['auth'])->name('dashboard');
Route::get('/user/confirm-password', fn () => view('spa'))->middleware('auth')->name('password.confirm');

require __DIR__.'/settings.php';

Route::get('/{any}', fn () => view('spa'))
    ->where('any', '^(?!admin|api).*');
