<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\QuestAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * Get current user
     * GET /api/user
     */
    public function user(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => $user->only([
                'id', 'name', 'email', 'role', 'nip', 'room',
                'unit_kerja', 'start_date', 'end_date', 'intern_type',
                'onboarding_status'
            ])
        ]);
    }

    /**
     * Get profile stats
     * GET /api/profile/stats
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Calculate total points
        $totalPoints = PointTransaction::where('user_id', $user->id)->sum('points');

        // Calculate level (1 level per 100 XP)
        $level = floor($totalPoints / 100) + 1;
        $currentXP = $totalPoints % 100;

        // Get streak
        $streak = $user->streak;
        
        // Get perfect days (days with streak bonus)
        $perfectDays = PointTransaction::where('user_id', $user->id)
            ->where('reference', PointTransaction::REF_STREAK_BONUS)
            ->count();

        // Calculate working days remaining
        $workingDaysRemaining = $user->getWorkingDaysRemaining();

        return response()->json([
            'stats' => [
                'total_points' => $totalPoints,
                'level' => $level,
                'current_xp' => $currentXP,
                'perfect_days' => $perfectDays,
                'working_days_remaining' => $workingDaysRemaining,
            ],
            'streak' => $streak ? [
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'last_activity_date' => $streak->last_activity_date,
            ] : null,
            'perfect_days' => $perfectDays,
        ]);
    }

    /**
     * Get point transactions
     * GET /api/point-transactions
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $limit = $request->input('limit', 20);
        
        $transactions = PointTransaction::where('user_id', $user->id)
            ->with('quest:id,title')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'points' => $tx->points,
                    'reference' => $tx->reference,
                    'description' => $tx->description,
                    'quest_id' => $tx->quest_id,
                    'quest_title' => $tx->quest?->title,
                    'created_at' => $tx->created_at,
                ];
            });

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}
