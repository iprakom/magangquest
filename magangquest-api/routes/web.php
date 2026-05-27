<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'framework' => 'Laravel ' . app()->version(),
        'vueVersion' => 'Vue 3',
    ]);
});

// TODO: Auth routes (Google SSO)
// Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
// Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// TODO: Protected routes (require auth)
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
//     Route::resource('/quests', QuestController::class);
// });

// TODO: API routes
// Route::prefix('api')->group(function () {
//     Route::apiResource('/quests', QuestApiController::class);
// });
