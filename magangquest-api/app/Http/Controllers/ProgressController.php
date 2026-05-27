<?php

namespace App\Http\Controllers;

use App\Models\QuestAssignment;
use App\Models\QuestProgress;
use App\Models\PointTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProgressController extends Controller
{
    /**
     * Get progress entries for a quest assignment
     * GET /api/quest-assignments/{id}/progress
     */
    public function index($assignmentId)
    {
        $user = Auth::user();
        
        $assignment = QuestAssignment::where('id', $assignmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $progressEntries = QuestProgress::where('quest_assignment_id', $assignmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'progress_entries' => $progressEntries,
            'total_points_earned' => $progressEntries->sum('points_earned'),
        ]);
    }

    /**
     * Add progress entry with optional evidence
     * POST /api/quest-assignments/{id}/progress
     */
    public function store(Request $request, $assignmentId)
    {
        $user = Auth::user();
        
        $assignment = QuestAssignment::where('id', $assignmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only allow progress on active assignments
        if (!in_array($assignment->status, [QuestAssignment::STATUS_ACTIVE, QuestAssignment::STATUS_IN_REVIEW])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add progress to this quest assignment',
            ], 400);
        }

        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $evidencePath = null;
        $evidenceFilename = null;

        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $filename = 'progress/' . $user->id . '_' . $assignmentId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $evidencePath = $file->storeAs('', $filename, 'public');
            $evidenceFilename = $file->getClientOriginalName();
        }

        // Create progress entry
        $progress = QuestProgress::create([
            'quest_assignment_id' => $assignmentId,
            'user_id' => $user->id,
            'notes' => $validated['notes'],
            'evidence_path' => $evidencePath,
            'evidence_filename' => $evidenceFilename,
            'points_earned' => 10,
        ]);

        // Award points for progress
        PointTransaction::createTransaction(
            $user->id,
            10,
            PointTransaction::REF_PROGRESS,
            $assignment->quest_id,
            $assignmentId,
            'Daily progress entry: ' . substr($validated['notes'], 0, 50)
        );

        return response()->json([
            'success' => true,
            'message' => 'Progress entry added successfully',
            'progress' => $progress,
            'points_earned' => 10,
        ], 201);
    }

    /**
     * Submit assignment for review after adding progress
     * POST /api/quest-assignments/{id}/submit-review
     */
    public function submitForReview($assignmentId)
    {
        $user = Auth::user();
        
        $assignment = QuestAssignment::where('id', $assignmentId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Only allow submit from active status
        if ($assignment->status !== QuestAssignment::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment cannot be submitted for review',
            ], 400);
        }

        $assignment->status = QuestAssignment::STATUS_IN_REVIEW;
        $assignment->submitted_at = now();
        $assignment->save();

        return response()->json([
            'success' => true,
            'message' => 'Quest submitted for review',
            'assignment' => $assignment,
        ]);
    }
}
