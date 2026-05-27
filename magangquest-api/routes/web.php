<?php

use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'framework' => 'Laravel ' . app()->version(),
        'vueVersion' => 'Vue 3',
    ]);
});

// Auth routes (Google SSO)
Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);

// Guest login page
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Login'))->name('login');
});

// Onboarding routes (require auth, pending status)
Route::middleware(['auth', 'onboarding.pending'])->group(function () {
    Route::get('/onboarding', fn () => Inertia::render('Onboarding'))->name('onboarding');
});

// Protected routes (require auth + active onboarding)
Route::middleware(['auth', 'onboarding.active'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/quests', fn () => Inertia::render('Quest/Index'))->name('quests');
});
