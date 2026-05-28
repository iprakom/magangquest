<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\QuestAssignment;
use App\Models\User;
use App\Mail\QuestSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class QuestAssignmentController extends Controller
{
    /**
     * Display the authenticated user's quest assignments
     * GET /api/quest-assignments/my
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = QuestAssignment::with(['quest', 'assignedBy', 'validatedBy'])
            ->where('user_id', $user->id);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by active status
        if ($request->boolean('active_only')) {
            $query->active();
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'assignments' => $assignments->items(),
            'pagination' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
            ],
            'wip_slots' => [
                'used' => $user->getUsedSlots(),
                'max' => $user->getMaxSlotCapacity(),
                'available' => $user->getAvailableSlots(),
            ],
        ]);
    }

    /**
     * Accept/assign a quest to the authenticated user
     * POST /api/quest-assignments
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'quest_id' => 'required|exists:quests,id',
        ]);

        $quest = Quest::findOrFail($validated['quest_id']);

        // Check if user is in Critical Zone (H-10 to H-0 working days)
        if ($user->isInCriticalZone()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa klaim task baru saat Fase Krusial',
                'critical_zone' => true,
                'working_days_remaining' => $user->getWorkingDaysRemaining(),
            ], 400);
        }

        // Only bounty quests can be claimed by players
        if ($quest->type !== Quest::TYPE_BOUNTY) {
            return response()->json([
                'success' => false,
                'message' => 'Only bounty quests can be claimed',
            ], 400);
        }

        // Check if quest is active
        if (!$quest->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This quest is no longer available',
            ], 400);
        }

        // Check if user already has an assignment for this quest
        $existingAssignment = QuestAssignment::where('quest_id', $quest->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an assignment for this quest',
            ], 400);
        }

        // Check if quest is already taken by someone else
        $takenAssignment = QuestAssignment::where('quest_id', $quest->id)
            ->where('status', '!=', QuestAssignment::STATUS_CANCELLED)
            ->first();

        if ($takenAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This quest has already been claimed',
            ], 400);
        }

        // WIP Slot check
        $slotWeight = Quest::getSlotWeight($quest->priority);
        $availableSlots = $user->getAvailableSlots();

        if ($availableSlots < $slotWeight) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient WIP slots. This quest requires {$slotWeight} slots, but you only have {$availableSlots} available.",
                'required_slots' => $slotWeight,
                'available_slots' => $availableSlots,
            ], 400);
        }

        // Create assignment
        $assignment = QuestAssignment::create([
            'quest_id' => $quest->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id, // Self-assigned for bounty
            'status' => QuestAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
            'slot_consumed' => $slotWeight,
        ]);

        $assignment->load(['quest', 'assignedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Quest claimed successfully',
            'assignment' => $assignment,
            'wip_slots' => [
                'used' => $user->getUsedSlots(),
                'max' => $user->getMaxSlotCapacity(),
                'available' => $user->getAvailableSlots(),
            ],
        ], 201);
    }

    /**
     * Update assignment status (lifecycle state)
     * PUT /api/quest-assignments/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $assignment = QuestAssignment::with('quest')->findOrFail($id);

        // Only the assigned user or admin can update status
        if ($assignment->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this assignment',
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:assigned,active,paused,in_review,approved,revise,cancelled,failed',
            'mentor_notes' => 'nullable|string|max:1000',
        ]);

        $newStatus = $validated['status'];
        $currentStatus = $assignment->status;

        // Validate status transitions
        $validTransitions = $this->getValidStatusTransitions($currentStatus);
        if (!in_array($newStatus, $validTransitions)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot transition from '{$currentStatus}' to '{$newStatus}'",
                'valid_transitions' => $validTransitions,
            ], 400);
        }

        // Update timestamps based on status
        $updateData = ['status' => $newStatus];

        switch ($newStatus) {
            case QuestAssignment::STATUS_ACTIVE:
                $updateData['started_at'] = now();
                break;
            case QuestAssignment::STATUS_PAUSED:
                $updateData['paused_at'] = now();
                break;
            case QuestAssignment::STATUS_IN_REVIEW:
                $updateData['submitted_at'] = now();
                break;
            case QuestAssignment::STATUS_APPROVED:
                $updateData['validated_at'] = now();
                $updateData['validated_by'] = $user->id;
                // Release slot when approved
                $updateData['slot_consumed'] = 0;
                break;
            case QuestAssignment::STATUS_CANCELLED:
            case QuestAssignment::STATUS_FAILED:
                // Release slot on cancellation/failure
                $updateData['slot_consumed'] = 0;
                break;
        }

        // Add mentor notes if provided
        if (isset($validated['mentor_notes'])) {
            $updateData['mentor_notes'] = $validated['mentor_notes'];
        }

        $assignment->update($updateData);
        $assignment->load(['quest', 'assignedBy', 'validatedBy']);

        // Send QuestSubmitted email to mentor when intern submits for review
        if ($newStatus === QuestAssignment::STATUS_IN_REVIEW) {
            $intern = $assignment->user;
            $mentor = User::find($intern->mentor_id);

            if ($mentor) {
                Mail::to($mentor->email)->send(new QuestSubmitted(
                    $intern->name,
                    $assignment->quest->title,
                    $assignment->submitted_at?->toDateTimeString()
                ));
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Assignment status updated to '{$newStatus}'",
            'assignment' => $assignment,
        ]);
    }

    /**
     * Get current WIP slot usage for authenticated user
     * GET /api/quest-assignments/wip-slots
     */
    public function getWipSlots()
    {
        $user = Auth::user();

        $activeAssignments = QuestAssignment::with('quest')
            ->where('user_id', $user->id)
            ->active()
            ->get();

        $slotBreakdown = $activeAssignments->map(function ($assignment) {
            return [
                'assignment_id' => $assignment->id,
                'quest_id' => $assignment->quest_id,
                'quest_title' => $assignment->quest->title,
                'slot_consumed' => $assignment->slot_consumed,
            ];
        });

        return response()->json([
            'wip_slots' => [
                'used' => $user->getUsedSlots(),
                'max' => $user->getMaxSlotCapacity(),
                'available' => $user->getAvailableSlots(),
                'utilization_percent' => round($user->getSlotUtilization(), 1),
                'status' => $user->getSlotStatus(),
            ],
            'active_assignments' => $slotBreakdown,
        ]);
    }

    /**
     * Get valid status transitions based on current status
     */
    private function getValidStatusTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            QuestAssignment::STATUS_ASSIGNED => [
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_CANCELLED,
            ],
            QuestAssignment::STATUS_ACTIVE => [
                QuestAssignment::STATUS_PAUSED,
                QuestAssignment::STATUS_IN_REVIEW,
                QuestAssignment::STATUS_CANCELLED,
            ],
            QuestAssignment::STATUS_PAUSED => [
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_CANCELLED,
            ],
            QuestAssignment::STATUS_IN_REVIEW => [
                QuestAssignment::STATUS_APPROVED,
                QuestAssignment::STATUS_REVISE,
            ],
            QuestAssignment::STATUS_REVISE => [
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_IN_REVIEW,
                QuestAssignment::STATUS_CANCELLED,
            ],
            default => [],
        };
    }
}
