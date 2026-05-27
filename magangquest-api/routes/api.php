<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OnboardingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Onboarding API routes (require auth)
Route::middleware(['auth'])->prefix('onboarding')->group(function () {
    Route::get('/status', [OnboardingController::class, 'checkStatus']);
    Route::post('/upload', [OnboardingController::class, 'uploadDocuments']);
    Route::post('/submit', [OnboardingController::class, 'submitForValidation']);
});

// Admin-only onboarding routes
Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::get('/onboarding/pending', [OnboardingController::class, 'getPendingUsers']);
    Route::post('/onboarding/{userId}/approve', [OnboardingController::class, 'approveUser']);
    Route::post('/onboarding/{userId}/reject', [OnboardingController::class, 'rejectUser']);
});
