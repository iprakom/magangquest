<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_id',
        'user_id',
        'assigned_by',
        'validated_by',
        'status',
        'assigned_at',
        'started_at',
        'paused_at',
        'submitted_at',
        'validated_at',
        'mentor_notes',
        'slot_consumed',
        'sla_deadline',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'slot_consumed' => 'integer',
        'sla_deadline' => 'datetime',
    ];

    // Status constants (from PRD)
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REVISE = 'revise';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Relationships
    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_ASSIGNED,
            self::STATUS_ACTIVE,
            self::STATUS_IN_REVIEW,
        ]);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePendingValidation($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }
}
