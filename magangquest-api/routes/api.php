<?php

use App\Http\Controllers\AdminOnboardingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestAssignmentController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MentorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Onboarding API routes (require auth)
Route::middleware(['auth'])->prefix('onboarding')->group(function () {
    Route::get('/status', [OnboardingController::class, 'checkStatus']);
    Route::post('/submit', [OnboardingController::class, 'submitPersonalInfo']);
    Route::post('/upload', [OnboardingController::class, 'uploadDocuments']);
    Route::post('/validate', [OnboardingController::class, 'submitForValidation']);
});

// Admin-only onboarding routes
Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::get('/onboarding/pending', [AdminOnboardingController::class, 'getPendingUsers']);
    Route::get('/onboarding/all', [AdminOnboardingController::class, 'getAllUsers']);
    Route::post('/onboarding/{userId}/approve', [AdminOnboardingController::class, 'approveUser']);
    Route::post('/onboarding/{userId}/reject', [AdminOnboardingController::class, 'rejectUser']);
});

// Quest API routes (require auth + active onboarding)
Route::middleware(['auth', 'check_onboarding'])->prefix('quests')->group(function () {
    Route::get('/', [QuestController::class, 'index']);
    Route::get('/bounty', [QuestController::class, 'bountyList']);
    Route::get('/{id}', [QuestController::class, 'show']);
    Route::post('/{id}/claim', [QuestController::class, 'claimBounty']);
});

Route::middleware(['auth', 'check_onboarding'])->prefix('quest-assignments')->group(function () {
    Route::get('/my', [QuestAssignmentController::class, 'index']);
    Route::get('/wip-slots', [QuestAssignmentController::class, 'getWipSlots']);
    Route::get('/{id}/progress', [ProgressController::class, 'index']);
    Route::post('/{id}/progress', [ProgressController::class, 'store']);
    Route::post('/{id}/submit-review', [ProgressController::class, 'submitForReview']);
});

// Admin-only quest management routes
Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::post('/quests', [QuestController::class, 'store']);
    Route::put('/quests/{id}', [QuestController::class, 'update']);
    Route::delete('/quests/{id}', [QuestController::class, 'destroy']);
    Route::post('/quest-assignments', [QuestAssignmentController::class, 'store']);
    Route::put('/quest-assignments/{id}/status', [QuestAssignmentController::class, 'updateStatus']);
    Route::get('/leaderboard', [LeaderboardController::class, 'adminIndex']);
    Route::get('/leaderboard/export', [LeaderboardController::class, 'adminExportCsv']);
    Route::get('/stats', [LeaderboardController::class, 'adminStats']);
});

// Holiday management routes (public for working day calculations, admin for CRUD)
Route::get('/holidays', [HolidayController::class, 'index']);
Route::get('/holidays/range', [HolidayController::class, 'range']);

Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::put('/holidays/{id}', [HolidayController::class, 'update']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);
});

// System settings routes (admin only)
Route::middleware(['auth', 'check_onboarding'])->prefix('admin')->group(function () {
    Route::get('/settings', [SystemSettingController::class, 'index']);
    Route::get('/settings/{key}', [SystemSettingController::class, 'show']);
    Route::put('/settings/{key}', [SystemSettingController::class, 'update']);
    Route::get('/settings/audit', [SystemSettingController::class, 'audit']);
});

// Leaderboard routes
Route::middleware(['auth', 'check_onboarding'])->prefix('leaderboard')->group(function () {
    Route::get('/', [LeaderboardController::class, 'index']);
    Route::get('/export', [LeaderboardController::class, 'export']);
});

// Mentor routes
Route::middleware(['auth', 'check_onboarding'])->prefix('mentor')->group(function () {
    Route::get('/idle-dashboard', [MentorController::class, 'idleDashboard']);
    Route::post('/assign', [MentorController::class, 'assignQuest']);
    Route::post('/quests', [MentorController::class, 'storeQuest']);
    Route::get('/interns', [MentorController::class, 'getInterns']);
    Route::get('/pending-validations', [MentorController::class, 'pendingValidations']);
    Route::put('/assignments/{id}/override-sla', [MentorController::class, 'overrideSla']);
    Route::put('/assignments/{id}/validate', [MentorController::class, 'validateAssignment']);
});
