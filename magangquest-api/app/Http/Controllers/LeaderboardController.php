<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\QuestAssignment;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaderboardController extends Controller
{
    /**
     * Get global leaderboard
     * GET /api/leaderboard
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,graduated,frozen',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $query = User::query()
            ->select('users.*')
            ->selectSub(function ($q) {
                $q->selectRaw('COALESCE(balance_after, 0)')
                    ->from('point_transactions')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'total_points')
            ->selectSub(function ($q) {
                $q->selectRaw('COUNT(*)')
                    ->from('quest_assignments')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', 'approved');
            }, 'completed_quests')
            ->selectSub(function ($q) {
                $q->selectRaw('COALESCE(current_streak, 0)')
                    ->from('streaks')
                    ->whereColumn('user_id', 'users.id');
            }, 'current_streak');

        // Filter by status
        if (!empty($validated['status'])) {
            $statusMap = [
                'active' => User::ONBOARDING_ACTIVE,
                'graduated' => User::ONBOARDING_FROZEN,
                'frozen' => User::ONBOARDING_RESTRICTED,
            ];
            $query->where('onboarding_status', $statusMap[$validated['status']]);
        }

        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        $leaderboard = $query->orderByDesc('total_points')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Add rank
        $leaderboard = $leaderboard->map(function ($user, $index) use ($offset) {
            $user->rank = $offset + $index + 1;
            return $user;
        });

        return response()->json([
            'leaderboard' => $leaderboard,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }

    /**
     * Export leaderboard to CSV
     * GET /api/leaderboard/export
     * Admin only
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can export leaderboard',
            ], 403);
        }

        $validated = $request->validate([
            'format' => 'nullable|in:csv,pdf',
            'status' => 'nullable|in:active,graduated,frozen',
        ]);

        $format = $validated['format'] ?? 'csv';

        $query = User::query()
            ->select('users.name', 'users.email', 'users.intern_type', 'users.start_date', 'users.end_date')
            ->selectSub(function ($q) {
                $q->selectRaw('COALESCE(balance_after, 0)')
                    ->from('point_transactions')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('id')
                    ->limit(1);
            }, 'total_points')
            ->selectSub(function ($q) {
                $q->selectRaw('COALESCE(current_streak, 0)')
                    ->from('streaks')
                    ->whereColumn('user_id', 'users.id');
            }, 'current_streak')
            ->selectSub(function ($q) {
                $q->selectRaw('COUNT(*)')
                    ->from('quest_assignments')
                    ->whereColumn('user_id', 'users.id')
                    ->where('status', 'approved');
            }, 'completed_quests')
            ->select('onboarding_status as final_status');

        // Filter by status
        if (!empty($validated['status'])) {
            $statusMap = [
                'active' => User::ONBOARDING_ACTIVE,
                'graduated' => User::ONBOARDING_FROZEN,
                'frozen' => User::ONBOARDING_RESTRICTED,
            ];
            $query->where('onboarding_status', $statusMap[$validated['status']]);
        }

        $users = $query->orderByDesc('total_points')->get();

        if ($format === 'csv') {
            return $this->exportCsv($users);
        }

        return response()->json([
            'success' => false,
            'message' => 'PDF export not implemented yet',
        ], 501);
    }

    /**
     * Export to CSV format
     */
    private function exportCsv($users)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leaderboard_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            
            // Header row
            fputcsv($handle, ['Rank', 'Name', 'Email', 'Intern Type', 'Total Points', 'Streak', 'Completed Quests', 'Final Status']);

            // Data rows
            $rank = 1;
            foreach ($users as $user) {
                fputcsv($handle, [
                    $rank++,
                    $user->name,
                    $user->email,
                    $user->intern_type ?? 'N/A',
                    $user->total_points ?? 0,
                    $user->current_streak ?? 0,
                    $user->completed_quests ?? 0,
                    $user->final_status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get user statistics for admin dashboard
     * GET /api/admin/statistics
     * Admin only
     */
    public function statistics()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view statistics',
            ], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('onboarding_status', User::ONBOARDING_ACTIVE)->count(),
            'pending_users' => User::where('onboarding_status', User::ONBOARDING_PENDING)->count(),
            'graduated_users' => User::where('onboarding_status', User::ONBOARDING_FROZEN)->count(),
            'total_quests' => \App\Models\Quest::count(),
            'active_quests' => \App\Models\Quest::where('is_active', true)->count(),
            'total_assignments' => QuestAssignment::count(),
            'completed_assignments' => QuestAssignment::where('status', 'approved')->count(),
            'average_points' => DB::table('point_transactions')
                ->selectRaw('AVG(balance_after) as avg')
                ->first()->avg ?? 0,
        ];

        return response()->json([
            'statistics' => $stats,
        ]);
    }
}
