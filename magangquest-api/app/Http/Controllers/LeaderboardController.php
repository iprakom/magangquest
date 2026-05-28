<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\QuestAssignment;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
     * Get admin leaderboard with full stats
     * GET /api/admin/leaderboard
     */
    public function adminIndex(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,graduated,frozen,all',
            'search' => 'nullable|string|max:255',
            'batch' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:200',
            'offset' => 'nullable|integer|min:0',
        ]);

        $query = User::query()
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.onboarding_status',
                'users.intern_type',
                'users.start_date',
                'users.end_date',
                'users.role'
            )
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
            ->selectSub(function ($q) {
                $q->selectRaw('COUNT(*)')
                    ->from('quest_assignments')
                    ->whereColumn('user_id', 'users.id');
            }, 'total_quests');

        // Filter by status
        $statusFilter = $validated['status'] ?? 'all';
        if ($statusFilter !== 'all') {
            $statusMap = [
                'active' => User::ONBOARDING_ACTIVE,
                'graduated' => User::ONBOARDING_FROZEN,
                'frozen' => User::ONBOARDING_RESTRICTED,
            ];
            if (isset($statusMap[$statusFilter])) {
                $query->where('onboarding_status', $statusMap[$statusFilter]);
            }
        }

        // Search by name or email
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        // Batch filter (by start_date period)
        if (!empty($validated['batch'])) {
            $query->where('users.start_date', 'like', "%{$validated['batch']}%");
        }

        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        // Order by total_points descending
        $users = $query->orderByDesc('total_points')
            ->offset($offset)
            ->limit($limit)
            ->get();

        // Add rank
        $users = $users->map(function ($user, $index) use ($offset) {
            $user->rank = $offset + $index + 1;
            $user->final_score = $user->total_points ?? 0;
            return $user;
        });

        // Get total count for pagination
        $totalQuery = User::query();
        if ($statusFilter !== 'all') {
            $statusMap = [
                'active' => User::ONBOARDING_ACTIVE,
                'graduated' => User::ONBOARDING_FROZEN,
                'frozen' => User::ONBOARDING_RESTRICTED,
            ];
            if (isset($statusMap[$statusFilter])) {
                $totalQuery->where('onboarding_status', $statusMap[$statusFilter]);
            }
        }
        $total = $totalQuery->count();

        return response()->json([
            'success' => true,
            'leaderboard' => $users,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Export admin leaderboard to CSV
     * GET /api/admin/leaderboard/export
     */
    public function adminExportCsv(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can export leaderboard',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:active,graduated,frozen,all',
            'batch' => 'nullable|string|max:255',
        ]);

        $query = User::query()
            ->select(
                'users.name',
                'users.email',
                'users.intern_type',
                'users.start_date',
                'users.end_date'
            )
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
        $statusFilter = $validated['status'] ?? 'all';
        if ($statusFilter !== 'all') {
            $statusMap = [
                'active' => User::ONBOARDING_ACTIVE,
                'graduated' => User::ONBOARDING_FROZEN,
                'frozen' => User::ONBOARDING_RESTRICTED,
            ];
            if (isset($statusMap[$statusFilter])) {
                $query->where('onboarding_status', $statusMap[$statusFilter]);
            }
        }

        // Batch filter
        if (!empty($validated['batch'])) {
            $query->where('users.start_date', 'like', "%{$validated['batch']}%");
        }

        $users = $query->orderByDesc('total_points')->get();

        return $this->exportAdminCsv($users);
    }

    /**
     * Export to CSV format - Admin version
     */
    private function exportAdminCsv($users)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leaderboard_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');

            // Header row matching spec
            fputcsv($handle, ['Rank', 'Name', 'Email', 'Total Poin', 'Streak Days', 'Completed Quests', 'Status', 'Final Score']);

            // Data rows
            $rank = 1;
            foreach ($users as $user) {
                fputcsv($handle, [
                    $rank++,
                    $user->name,
                    $user->email,
                    $user->total_points ?? 0,
                    $user->current_streak ?? 0,
                    $user->completed_quests ?? 0,
                    $this->formatStatus($user->final_status),
                    $user->total_points ?? 0,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Format status for display
     */
    private function formatStatus($status)
    {
        $statusMap = [
            'restricted' => 'Frozen',
            'pending' => 'Pending',
            'active' => 'Active',
            'frozen' => 'Graduated',
        ];
        return $statusMap[$status] ?? $status;
    }

    /**
     * Get admin statistics dashboard
     * GET /api/admin/stats
     */
    public function adminStats()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view statistics',
            ], 403);
        }

        $totalUsers = User::count();
        $activeUsers = User::where('onboarding_status', User::ONBOARDING_ACTIVE)->count();
        $graduatedUsers = User::where('onboarding_status', User::ONBOARDING_FROZEN)->count();
        $frozenUsers = User::where('onboarding_status', User::ONBOARDING_RESTRICTED)->count();
        $pendingUsers = User::where('onboarding_status', User::ONBOARDING_PENDING)->count();

        $totalQuests = \App\Models\Quest::count();
        $activeQuests = \App\Models\Quest::where('is_active', true)->count();
        $totalAssignments = QuestAssignment::count();
        $completedAssignments = QuestAssignment::where('status', 'approved')->count();

        // Average points (from latest transaction per user)
        $avgPoints = DB::table('point_transactions as pt1')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('point_transactions as pt2')
                    ->whereColumn('pt2.user_id', 'pt1.user_id')
                    ->groupBy('pt2.user_id');
            })
            ->avg('balance_after') ?? 0;

        // Top performer
        $topPerformer = DB::table('point_transactions as pt1')
            ->select('users.name', 'users.email', 'pt1.balance_after as total_points')
            ->join('users', 'users.id', '=', 'pt1.user_id')
            ->whereIn('pt1.id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('point_transactions as pt2')
                    ->whereColumn('pt2.user_id', 'pt1.user_id')
                    ->groupBy('pt2.user_id');
            })
            ->orderByDesc('pt1.balance_after')
            ->first();

        // Completion rate (% users graduated)
        $completionRate = $totalUsers > 0 ? round(($graduatedUsers / $totalUsers) * 100, 1) : 0;

        // Quest completion stats
        $questCompletionRate = $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 1) : 0;

        // Users by intern type
        $usersByType = DB::table('users')
            ->select('intern_type', DB::raw('COUNT(*) as count'))
            ->groupBy('intern_type')
            ->pluck('count', 'intern_type');

        // Average completed quests per user
        $avgCompletedQuests = $totalUsers > 0 ? round($completedAssignments / $totalUsers, 1) : 0;

        // Top 10 leaderboard
        $top10Leaderboard = User::query()
            ->select('users.id', 'users.name', 'users.email')
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
            ->orderByDesc('total_points')
            ->limit(10)
            ->get()
            ->map(function ($u, $i) {
                $u->rank = $i + 1;
                return $u;
            });

        $stats = [
            'total_intern' => $totalUsers,
            'active_intern' => $activeUsers,
            'graduated_intern' => $graduatedUsers,
            'frozen_intern' => $frozenUsers,
            'pending_intern' => $pendingUsers,
            'average_points' => round($avgPoints, 2),
            'top_performer' => $topPerformer ? [
                'name' => $topPerformer->name,
                'email' => $topPerformer->email,
                'total_points' => $topPerformer->total_points ?? 0,
            ] : null,
            'completion_rate' => $completionRate,
            'total_quests' => $totalQuests,
            'active_quests' => $activeQuests,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
            'quest_completion_rate' => $questCompletionRate,
            'avg_completed_quests' => $avgCompletedQuests,
            'users_by_type' => $usersByType,
            'top_10_leaderboard' => $top10Leaderboard,
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }
}
