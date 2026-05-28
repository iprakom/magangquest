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
Route::get('/auth/google/config', [GoogleAuthController::class, 'verifyConfig']);

// Guest login page
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Login'))->name('login');
});

// Onboarding routes (require auth, pending status)
Route::middleware(['auth', 'onboarding.pending'])->group(function () {
    Route::get('/onboarding', function () {
        return Inertia::render('Onboarding', [
            'user' => Auth::user() ? [
                'name' => Auth::user()->name,
                'nip' => Auth::user()->nip,
                'unit_kerja' => Auth::user()->unit_kerja,
                'start_date' => Auth::user()->start_date?->format('Y-m-d'),
                'end_date' => Auth::user()->end_date?->format('Y-m-d'),
                'room' => Auth::user()->room,
            ] : null,
        ]);
    })->name('onboarding');
    Route::get('/onboarding/upload', fn () => Inertia::render('OnboardingUpload'))->name('onboarding.upload');
});

// Protected routes (require auth + active onboarding)
Route::middleware(['auth', 'onboarding.active'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/quests', fn () => Inertia::render('Quest/Index'))->name('quests');
});
