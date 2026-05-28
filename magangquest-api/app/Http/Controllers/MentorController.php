<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Quest;
use App\Models\QuestAssignment;
use App\Models\PointTransaction;
use App\Mail\QuestAssigned;
use App\Mail\QuestSubmitted;
use App\Mail\QuestApproved;
use App\Mail\QuestNeedsRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MentorController extends Controller
{
    /**
     * Get idle dashboard - room-based monitoring
     * GET /api/mentor/idle-dashboard
     * Mentor or Admin only
     */
    public function idleDashboard(Request $request)
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $validated = $request->validate([
            'room' => 'nullable|string',
            'mentor_id' => 'nullable|integer|exists:users,id',
        ]);

        // Get all active interns
        $query = User::where('onboarding_status', User::ONBOARDING_ACTIVE);

        // Filter by mentor if user is a mentor
        if ($user->isMentor()) {
            $query->where('mentor_id', $user->id);
        } elseif (!empty($validated['mentor_id'])) {
            $query->where('mentor_id', $validated['mentor_id']);
        }

        // Filter by room if provided
        if (!empty($validated['room'])) {
            $query->where('room', $validated['room']);
        }

        $interns = $query->with(['questAssignments' => function ($q) {
            $q->whereIn('status', [
                QuestAssignment::STATUS_ASSIGNED,
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_IN_REVIEW,
            ]);
        }])->get();

        // Calculate slot utilization per intern
        $dashboardData = $interns->map(function ($intern) {
            $maxSlots = $intern->getMaxSlotCapacity();
            $usedSlots = $intern->getUsedSlots();
            $utilization = $maxSlots > 0 ? ($usedSlots / $maxSlots) * 100 : 0;

            // Determine status color
            if ($utilization >= 100) {
                $statusColor = 'gray'; // Overloaded
            } elseif ($utilization <= 50) {
                $statusColor = 'red'; // Idle (<=50%)
            } else {
                $statusColor = 'yellow'; // Optimal (51-99%)
            }

            return [
                'user_id' => $intern->id,
                'name' => $intern->name,
                'email' => $intern->email,
                'room' => $intern->room ?? 'Unassigned',
                'slot_utilization' => [
                    'used' => $usedSlots,
                    'max' => $maxSlots,
                    'percent' => round($utilization, 1),
                ],
                'status' => $intern->getSlotStatus(),
                'status_color' => $statusColor,
                'active_assignments' => $intern->questAssignments->count(),
                'current_streak' => $intern->streak?->current_streak ?? 0,
                'is_grace_period' => $intern->is_grace_period,
                'is_critical_zone' => $intern->is_critical_zone,
                'working_days_remaining' => $intern->getWorkingDaysRemaining(),
            ];
        });

        // Group by room
        $groupedByRoom = $dashboardData->groupBy('room');

        return response()->json([
            'dashboard' => $dashboardData,
            'by_room' => $groupedByRoom,
            'summary' => [
                'total_interns' => $interns->count(),
                'idle_count' => $dashboardData->where('status', 'idle')->count(),
                'optimal_count' => $dashboardData->where('status', 'optimal')->count(),
                'overloaded_count' => $dashboardData->where('status', 'overloaded')->count(),
            ],
        ]);
    }

    /**
     * Assign quest to specific intern
     * POST /api/mentor/assign
     * Mentor or Admin only
     */
    public function assignQuest(Request $request)
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $validated = $request->validate([
            'quest_id' => 'required|exists:quests,id',
            'user_id' => 'required|exists:users,id',
            'sla_deadline' => 'nullable|date|after_or_equal:today',
        ]);

        $quest = Quest::findOrFail($validated['quest_id']);
        $targetUser = User::findOrFail($validated['user_id']);

        // Only assigned type quests can be assigned by mentor
        if ($quest->type !== Quest::TYPE_ASSIGNED) {
            return response()->json([
                'success' => false,
                'message' => 'Only assigned type quests can be assigned to specific interns',
            ], 400);
        }

        // Check if target user is active
        if (!$targetUser->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Target user is not active',
            ], 400);
        }

        // Check if target user is in Critical Zone based on working days
        if ($targetUser->isInCriticalZone()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa assign task baru saat Fase Krusial (H-10 hingga H-0)',
                'critical_zone' => true,
                'working_days_remaining' => $targetUser->getWorkingDaysRemaining(),
            ], 400);
        }

        // Check if user is in grace period
        if ($targetUser->is_grace_period) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot assign new quests to users in grace period',
            ], 400);
        }

        // Check if quest is active
        if (!$quest->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This quest is no longer active',
            ], 400);
        }

        // Check if user already has this assignment
        $exists = QuestAssignment::where('quest_id', $quest->id)
            ->where('user_id', $targetUser->id)
            ->where('status', '!=', QuestAssignment::STATUS_CANCELLED)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'User already has this quest assigned',
            ], 400);
        }

        // Check slot capacity
        $slotWeight = $quest->slot_weight;
        if ($targetUser->getAvailableSlots() < $slotWeight) {
            return response()->json([
                'success' => false,
                'message' => 'Target user does not have enough WIP slots',
                'required_slots' => $slotWeight,
                'available_slots' => $targetUser->getAvailableSlots(),
            ], 400);
        }

        // Create assignment
        $assignment = QuestAssignment::create([
            'quest_id' => $quest->id,
            'user_id' => $targetUser->id,
            'assigned_by' => $user->id,
            'status' => QuestAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
            'slot_consumed' => $slotWeight,
            'sla_deadline' => $validated['sla_deadline'] ?? null,
        ]);

        $assignment->load(['quest', 'user', 'assignedBy']);

        // Send quest assigned email to intern
        Mail::to($targetUser->email)->send(new QuestAssigned(
            $targetUser->name,
            $quest->title,
            isset($validated['sla_deadline']) ? $validated['sla_deadline'] : null
        ));

        return response()->json([
            'success' => true,
            'message' => 'Quest assigned successfully',
            'assignment' => $assignment,
        ], 201);
    }

    /**
     * Override SLA for a quest assignment
     * PUT /api/mentor/assignments/{id}/override-sla
     * Mentor or Admin only
     */
    public function overrideSla(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $assignment = QuestAssignment::findOrFail($id);

        $validated = $request->validate([
            'sla_deadline' => 'required|date|after_or_equal:today',
            'reason' => 'required|string|max:500',
        ]);

        // Update SLA deadline
        $assignment->sla_deadline = $validated['sla_deadline'];
        $assignment->mentor_notes = ($assignment->mentor_notes ? $assignment->mentor_notes . "\n" : '') . 
            "[SLA Override] " . $validated['reason'] . " - " . $user->name . " at " . now()->toDateTimeString();
        $assignment->save();

        return response()->json([
            'success' => true,
            'message' => 'SLA deadline overridden successfully',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Get pending validations for mentor
     * GET /api/mentor/pending-validations
     * Mentor or Admin only
     */
    public function pendingValidations()
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $query = QuestAssignment::with(['user', 'quest'])
            ->where('status', QuestAssignment::STATUS_IN_REVIEW);

        // If mentor, only show assignments for their mentees
        if ($user->isMentor()) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('mentor_id', $user->id);
            });
        }

        $pendingAssignments = $query->orderBy('submitted_at', 'asc')->get();

        return response()->json([
            'pending_validations' => $pendingAssignments,
            'count' => $pendingAssignments->count(),
        ]);
    }

    /**
     * Validate/approve or revise an assignment
     * PUT /api/mentor/assignments/{id}/validate
     * Mentor or Admin only
     */
    public function validateAssignment(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $assignment = QuestAssignment::with(['quest', 'user'])->findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|in:approve,revise',
            'mentor_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['action'] === 'approve') {
            $assignment->status = QuestAssignment::STATUS_APPROVED;
            $assignment->validated_at = now();
            $assignment->validated_by = $user->id;
            $assignment->slot_consumed = 0;
            
            if (!empty($validated['mentor_notes'])) {
                $assignment->mentor_notes = ($assignment->mentor_notes ? $assignment->mentor_notes . "\n" : '') . 
                    "[Approved] " . $validated['mentor_notes'];
            }

            // Award +100 points for quest completion
            PointTransaction::createTransaction(
                $assignment->user_id,
                100,
                PointTransaction::REF_QUEST_APPROVED,
                $assignment->quest_id,
                $assignment->id,
                'Quest completed: ' . $assignment->quest->title
            );

            $message = 'Quest approved and +100 points awarded';
        } else {
            $assignment->status = QuestAssignment::STATUS_REVISE;
            
            if (!empty($validated['mentor_notes'])) {
                $assignment->mentor_notes = ($assignment->mentor_notes ? $assignment->mentor_notes . "\n" : '') . 
                    "[Revision Required] " . $validated['mentor_notes'];
            }

            // Apply revise penalty: -10 points
            PointTransaction::createTransaction(
                $assignment->user_id,
                -10,
                PointTransaction::REF_REVISE_PENALTY,
                $assignment->quest_id,
                $assignment->id,
                'Quest returned for revision: ' . ($validated['mentor_notes'] ?? 'No notes provided')
            );

            $message = 'Quest returned for revision with -10 point penalty';
        }

        $assignment->save();

        // Send email notification to intern
        if ($validated['action'] === 'approve') {
            Mail::to($assignment->user->email)->send(new QuestApproved(
                $assignment->user->name,
                $assignment->quest->title,
                100
            ));
        } else {
            Mail::to($assignment->user->email)->send(new QuestNeedsRevision(
                $assignment->user->name,
                $assignment->quest->title,
                $validated['mentor_notes'] ?? 'Tidak ada catatan'
            ));
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'assignment' => $assignment,
        ]);
    }

    /**
     * Create a new quest as mentor (bounty or assigned type)
     * POST /api/mentor/quests
     * Mentor or Admin only
     */
    public function storeQuest(Request $request)
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:bounty,assigned',
            'priority' => 'required|in:high,mid,low',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        // Calculate slot weight based on priority
        $slotWeight = Quest::getSlotWeight($validated['priority']);

        $quest = Quest::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'slot_weight' => $slotWeight,
            'start_date' => now(),
            'due_date' => $validated['due_date'] ?? null,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $quest->load(['creator']);

        return response()->json([
            'success' => true,
            'message' => 'Quest created successfully',
            'quest' => $quest,
        ], 201);
    }

    /**
     * Get list of active interns for mentor dropdown
     * GET /api/mentor/interns
     * Mentor or Admin only
     */
    public function getInterns()
    {
        $user = Auth::user();

        if (!$user->isMentor() && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Mentor or Admin role required.',
            ], 403);
        }

        $query = User::where('onboarding_status', User::ONBOARDING_ACTIVE)
            ->where('role', User::ROLE_PLAYER);

        // If mentor, only show their mentees
        if ($user->isMentor()) {
            $query->where('mentor_id', $user->id);
        }

        $interns = $query->get()->map(function ($intern) {
            return [
                'id' => $intern->id,
                'name' => $intern->name,
                'email' => $intern->email,
                'room' => $intern->room,
                'slots' => [
                    'used' => $intern->getUsedSlots(),
                    'max' => $intern->getMaxSlotCapacity(),
                    'available' => $intern->getAvailableSlots(),
                ],
            ];
        });

        return response()->json([
            'interns' => $interns,
        ]);
    }
}
