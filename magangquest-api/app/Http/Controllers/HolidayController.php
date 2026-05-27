<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HolidayController extends Controller
{
    /**
     * List all holidays
     * GET /api/holidays
     */
    public function index(Request $request)
    {
        $query = Holiday::query();

        // Filter by year if provided
        if ($request->has('year') && $request->year) {
            $query->whereYear('date', $request->year);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by recurring
        if ($request->has('recurring')) {
            $query->where('is_recurring', $request->boolean('recurring'));
        }

        $holidays = $query->orderBy('date', 'asc')->get();

        return response()->json([
            'holidays' => $holidays,
        ]);
    }

    /**
     * Store a new holiday
     * POST /api/holidays
     * Admin only
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can create holidays',
            ], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:national,local,company',
            'is_recurring' => 'boolean',
        ]);

        // Check for duplicate
        $exists = Holiday::where('date', $validated['date'])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A holiday already exists on this date',
            ], 400);
        }

        $holiday = Holiday::create([
            'date' => $validated['date'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_recurring' => $validated['is_recurring'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Holiday created successfully',
            'holiday' => $holiday,
        ], 201);
    }

    /**
     * Update a holiday
     * PUT /api/holidays/{id}
     * Admin only
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update holidays',
            ], 403);
        }

        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:national,local,company',
            'is_recurring' => 'boolean',
        ]);

        // Check for duplicate date if changing
        if (isset($validated['date']) && $validated['date'] !== $holiday->date->format('Y-m-d')) {
            $exists = Holiday::where('date', $validated['date'])->where('id', '!=', $id)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A holiday already exists on this date',
                ], 400);
            }
        }

        $holiday->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Holiday updated successfully',
            'holiday' => $holiday,
        ]);
    }

    /**
     * Delete a holiday
     * DELETE /api/holidays/{id}
     * Admin only
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete holidays',
            ], 403);
        }

        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully',
        ]);
    }

    /**
     * Get holidays for date range (for SLA calculations)
     * GET /api/holidays/range
     */
    public function range(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $holidays = Holiday::whereBetween('date', [
            $validated['start_date'],
            $validated['end_date'],
        ])->get();

        return response()->json([
            'holidays' => $holidays,
            'count' => $holidays->count(),
        ]);
    }
}
