<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    // Cache the settings
    protected static ?array $cache = null;

    // Get setting value
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::loadCache();
        }

        if (!isset(self::$cache[$key])) {
            return $default;
        }

        $setting = self::$cache[$key];

        return match($setting['type']) {
            'integer' => (int) $setting['value'],
            'boolean' => filter_var($setting['value'], FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting['value'], true),
            default => $setting['value'],
        };
    }

    // Set setting value
    public static function set(string $key, mixed $value): void
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return;
        }

        $setting->value = is_array($value) ? json_encode($value) : (string) $value;
        $setting->save();

        // Clear cache
        self::$cache = null;
    }

    // Load all settings into cache
    protected static function loadCache(): void
    {
        $settings = self::all();
        self::$cache = $settings->keyBy('key')->toArray();
    }

    // Clear cache
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
