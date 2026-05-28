<?php

namespace App\Http\Controllers;

use App\Models\Quest;
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
