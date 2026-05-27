<?php

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestAssignmentController;
use Illuminate\Support\Facades\Route;

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

// Quest API routes (require auth + active onboarding)
Route::middleware(['auth', 'check_onboarding'])->prefix('quests')->group(function () {
    Route::get('/', [QuestController::class, 'index']);
    Route::get('/{id}', [QuestController::class, 'show']);
});

Route::middleware(['auth', 'check_onboarding'])->prefix('quest-assignments')->group(function () {
    Route::get('/my', [QuestAssignmentController::class, 'index']);
    Route::get('/wip-slots', [QuestAssignmentController::class, 'getWipSlots']);
});

// Admin-only quest management routes
Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::post('/quests', [QuestController::class, 'store']);
    Route::put('/quests/{id}', [QuestController::class, 'update']);
    Route::delete('/quests/{id}', [QuestController::class, 'destroy']);
    Route::post('/quest-assignments', [QuestAssignmentController::class, 'store']);
    Route::put('/quest-assignments/{id}/status', [QuestAssignmentController::class, 'updateStatus']);
});
