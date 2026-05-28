<?php

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\QuestAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestController extends Controller
{
    /**
     * Display a listing of quests with filters
     * GET /api/quests
     */
    public function index(Request $request)
    {
        $query = Quest::with(['creator', 'assignments'])
            ->withCount(['assignments as assignments_count']);

        // Filter by type (supports single type or array of types: assigned, bounty, usulan)
        if ($request->has('type') && $request->type) {
            $types = is_array($request->type) ? $request->type : explode(',', $request->type);
            $query->whereIn('type', $types);
        }

        // Also support filter as separate boolean flags for assigned/bounty/usulan
        $typeFilters = [];
        if ($request->boolean('assigned')) {
            $typeFilters[] = Quest::TYPE_ASSIGNED;
        }
        if ($request->boolean('bounty')) {
            $typeFilters[] = Quest::TYPE_BOUNTY;
        }
        if ($request->boolean('usulan')) {
            $typeFilters[] = Quest::TYPE_USULAN;
        }
        if (!empty($typeFilters)) {
            $query->whereIn('type', $typeFilters);
        }

        // Filter by difficulty/priority
        if ($request->has('difficulty') && $request->difficulty) {
            $query->where('priority', $request->difficulty);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by available for bounty claim
        if ($request->boolean('available_for_claim')) {
            $query->availableForClaim();
        }

        $quests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'quests' => $quests->items(),
            'pagination' => [
                'current_page' => $quests->currentPage(),
                'last_page' => $quests->lastPage(),
                'per_page' => $quests->perPage(),
                'total' => $quests->total(),
            ],
        ]);
    }

    /**
     * Store a newly created quest template
     * POST /api/quests
     * Admin only
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Check admin permission
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can create quests',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:assigned,bounty,usulan',
            'priority' => 'required|in:high,mid,low',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        // Calculate slot weight based on priority
        $slotWeight = Quest::getSlotWeight($validated['priority']);

        $quest = Quest::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'slot_weight' => $slotWeight,
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
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
     * Display the specified quest
     * GET /api/quests/{id}
     */
    public function show($id)
    {
        $quest = Quest::with([
            'creator',
            'assignments.user',
            'assignments.assignedBy',
        ])->findOrFail($id);

        // Add user-specific assignment if authenticated
        $user = Auth::user();
        $userAssignment = null;
        if ($user) {
            $userAssignment = $quest->assignments()
                ->where('user_id', $user->id)
                ->first();
        }

        return response()->json([
            'quest' => $quest,
            'user_assignment' => $userAssignment,
        ]);
    }

    /**
     * Update the specified quest
     * PUT /api/quests/{id}
     * Admin only
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Check admin permission
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update quests',
            ], 403);
        }

        $quest = Quest::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:assigned,bounty,usulan',
            'priority' => 'sometimes|in:high,mid,low',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        // Recalculate slot weight if priority changed
        if (isset($validated['priority'])) {
            $validated['slot_weight'] = Quest::getSlotWeight($validated['priority']);
        }

        $quest->update($validated);
        $quest->load(['creator']);

        return response()->json([
            'success' => true,
            'message' => 'Quest updated successfully',
            'quest' => $quest,
        ]);
    }

    /**
     * Get list of available bounty quests
     * GET /api/quests/bounty
     */
    public function bountyList(Request $request)
    {
        $user = Auth::user();

        $query = Quest::with(['creator'])
            ->where('type', Quest::TYPE_BOUNTY)
            ->where('is_active', true)
            ->whereDoesntHave('assignments', function ($q) {
                $q->where('status', '!=', QuestAssignment::STATUS_CANCELLED);
            });

        $quests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get user's WIP slot info
        $wipSlots = [
            'used' => $user->getUsedSlots(),
            'max' => $user->getMaxSlotCapacity(),
            'available' => $user->getAvailableSlots(),
        ];

        return response()->json([
            'quests' => $quests->items(),
            'pagination' => [
                'current_page' => $quests->currentPage(),
                'last_page' => $quests->lastPage(),
                'per_page' => $quests->perPage(),
                'total' => $quests->total(),
            ],
            'wip_slots' => $wipSlots,
        ]);
    }

    /**
     * Claim a bounty quest
     * POST /api/quests/{id}/claim
     */
    public function claimBounty(Request $request, $id)
    {
        $user = Auth::user();
        $quest = Quest::findOrFail($id);

        // Only bounty quests can be claimed
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
            'assigned_by' => $user->id,
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
     * Remove the specified quest
     * DELETE /api/quests/{id}
     * Admin only
     */
    public function destroy($id)
    {
        $user = Auth::user();

        // Check admin permission
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete quests',
            ], 403);
        }

        $quest = Quest::findOrFail($id);

        // Soft delete the quest
        $quest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quest deleted successfully',
        ]);
    }
}
