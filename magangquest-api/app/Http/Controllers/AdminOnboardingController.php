<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PointTransaction;
use App\Models\SystemSetting;
use App\Mail\OnboardingApproved;
use App\Mail\OnboardingRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class AdminOnboardingController extends Controller
{
    /**
     * Display admin onboarding management page
     */
    public function index()
    {
        $pendingUsers = User::where('onboarding_status', User::ONBOARDING_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        $allUsers = User::where('role', User::ROLE_PLAYER)
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('AdminOnboarding', [
            'pendingUsers' => $pendingUsers,
            'allUsers' => $allUsers,
        ]);
    }

    /**
     * Get pending users list (API)
     */
    public function getPendingUsers()
    {
        $users = User::where('onboarding_status', User::ONBOARDING_PENDING)
            ->with(['streak'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Get all interns (API)
     */
    public function getAllUsers()
    {
        $users = User::where('role', User::ROLE_PLAYER)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
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
        $bonusPoints = SystemSetting::get('onboarding_bonus_points', 50);
        PointTransaction::createTransaction(
            $user->id,
            $bonusPoints,
            PointTransaction::REF_ONBOARDING_BONUS,
            notes: 'Onboarding bonus for completing document validation'
        );

        // Send approval email
        Mail::to($user->email)->send(new OnboardingApproved($user->name, $bonusPoints));

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

        $reason = $request->reason;

        // Reset to restricted status and clear document
        if ($user->document_path && Storage::disk('public')->exists($user->document_path)) {
            Storage::disk('public')->delete($user->document_path);
        }

        $user->document_path = null;
        $user->onboarding_status = User::ONBOARDING_RESTRICTED;
        $user->save();

        // Send rejection email
        Mail::to($user->email)->send(new OnboardingRejected($user->name, $reason));

        return response()->json([
            'success' => true,
            'message' => 'User rejected. Reason: ' . $reason,
        ]);
    }
}
