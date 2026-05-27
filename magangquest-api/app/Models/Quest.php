<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'type',
        'priority',
        'slot_weight',
        'start_date',
        'due_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'is_active' => 'boolean',
        'slot_weight' => 'integer',
    ];

    // Slot weight constants based on PRD
    const SLOT_WEIGHT_HIGH = 4;
    const SLOT_WEIGHT_MID = 2;
    const SLOT_WEIGHT_LOW = 1;

    // Quest types
    const TYPE_ASSIGNED = 'assigned';
    const TYPE_BOUNTY = 'bounty';
    const TYPE_USULAN = 'usulan';

    // Priority levels
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MID = 'mid';
    const PRIORITY_LOW = 'low';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(QuestAssignment::class);
    }

    // Methods
    public static function getSlotWeight(string $priority): int
    {
        return match($priority) {
            self::PRIORITY_HIGH => self::SLOT_WEIGHT_HIGH,
            self::PRIORITY_MID => self::SLOT_WEIGHT_MID,
            self::PRIORITY_LOW => self::SLOT_WEIGHT_LOW,
            default => self::SLOT_WEIGHT_MID,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBounty($query)
    {
        return $query->where('type', self::TYPE_BOUNTY);
    }

    public function scopeAvailableForClaim($query)
    {
        return $query->where('type', self::TYPE_BOUNTY)
                     ->where('is_active', true)
                     ->whereDoesntHave('assignments', function ($q) {
                         $q->where('status', '!=', 'cancelled');
                     });
    }
}
