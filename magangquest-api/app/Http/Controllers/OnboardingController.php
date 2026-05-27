<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PointTransaction;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OnboardingController extends Controller
{
    /**
     * Get current onboarding status
     */
    public function checkStatus()
    {
        $user = Auth::user();

        return response()->json([
            'status' => $user->onboarding_status,
            'document_uploaded' => !empty($user->document_path),
            'can_submit' => !empty($user->document_path) && $user->onboarding_status === User::ONBOARDING_RESTRICTED,
            'can_upload' => $user->onboarding_status === User::ONBOARDING_RESTRICTED || $user->onboarding_status === User::ONBOARDING_PENDING,
            'is_active' => $user->isActive(),
        ]);
    }

    /**
     * Upload documents
     */
    public function uploadDocuments(Request $request)
    {
        $user = Auth::user();

        // Check if user can upload
        if (!in_array($user->onboarding_status, [User::ONBOARDING_RESTRICTED, User::ONBOARDING_PENDING])) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot upload documents at this stage',
            ], 400);
        }

        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        $file = $request->file('document');
        $filename = 'documents/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

        // Delete old document if exists
        if ($user->document_path && Storage::disk('public')->exists($user->document_path)) {
            Storage::disk('public')->delete($user->document_path);
        }

        // Store new document
        $path = $file->storeAs('', $filename, 'public');

        // Update user document_path
        $user->document_path = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document_path' => $path,
        ]);
    }

    /**
     * Submit for validation
     */
    public function submitForValidation()
    {
        $user = Auth::user();

        // Check if user can submit
        if (empty($user->document_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload your documents first',
            ], 400);
        }

        if ($user->onboarding_status !== User::ONBOARDING_RESTRICTED) {
            return response()->json([
                'success' => false,
                'message' => 'You can only submit for validation when in restricted status',
            ], 400);
        }

        $user->onboarding_status = User::ONBOARDING_PENDING;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Your documents have been submitted for validation',
            'status' => $user->onboarding_status,
        ]);
    }

    /**
     * Admin: Get pending users for validation
     */
    public function getPendingUsers()
    {
        $users = User::where('onboarding_status', User::ONBOARDING_PENDING)
            ->with(['streak'])
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Admin: Approve user onboarding
     */
    public function approveUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->onboarding_status !== User::ONBOARDING_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'User is not pending validation',
            ], 400);
        }

        // Activate the user
        $user->onboarding_status = User::ONBOARDING_ACTIVE;
        $user->save();

        // Grant onboarding bonus points
        $bonusPoints = SystemSetting::get('onboarding_bonus_points', 100);
        PointTransaction::createTransaction(
            $user->id,
            $bonusPoints,
            PointTransaction::REF_ONBOARDING_BONUS,
            notes: 'Onboarding bonus for completing document validation'
        );

        return response()->json([
            'success' => true,
            'message' => 'User approved successfully',
            'user' => $user,
            'bonus_points' => $bonusPoints,
        ]);
    }

    /**
     * Admin: Reject user onboarding
     */
    public function rejectUser(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if ($user->onboarding_status !== User::ONBOARDING_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'User is not pending validation',
            ], 400);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Reset to restricted status and clear document
        if ($user->document_path && Storage::disk('public')->exists($user->document_path)) {
            Storage::disk('public')->delete($user->document_path);
        }

        $user->document_path = null;
        $user->onboarding_status = User::ONBOARDING_RESTRICTED;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User rejected. Reason: ' . $request->reason,
        ]);
    }
}
