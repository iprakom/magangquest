<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings
     * GET /api/admin/settings
     * Admin only
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view system settings',
            ], 403);
        }

        $settings = SystemSetting::all();

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Get a specific setting value
     * GET /api/admin/settings/{key}
     */
    public function show($key)
    {
        $value = SystemSetting::get($key);

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Update a system setting
     * PUT /api/admin/settings/{key}
     * Admin only
     */
    public function update(Request $request, $key)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update system settings',
            ], 403);
        }

        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        $validated = $request->validate([
            'value' => 'required',
        ]);

        // Update value with proper type casting
        $value = $validated['value'];
        
        if ($setting->type === 'integer') {
            $value = (int) $value;
        } elseif ($setting->type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        } elseif ($setting->type === 'json') {
            $value = is_array($value) ? json_encode($value) : $value;
        }

        $setting->value = (string) $value;
        $setting->save();

        // Clear cache to apply changes immediately
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'setting' => [
                'key' => $setting->key,
                'value' => $value,
                'type' => $setting->type,
            ],
        ]);
    }

    /**
     * Get setting change audit trail
     * GET /api/admin/settings/audit
     * Admin only
     */
    public function audit(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can view audit logs',
            ], 403);
        }

        // This would need a settings_audit table
        // For now, return empty - can be implemented later
        return response()->json([
            'audit_logs' => [],
        ]);
    }
}
