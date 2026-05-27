<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_assignment_id',
        'user_id',
        'notes',
        'evidence_path',
        'evidence_filename',
        'points_earned',
    ];

    protected $casts = [
        'points_earned' => 'integer',
    ];

    // Relationships
    public function questAssignment(): BelongsTo
    {
        return $this->belongsTo(QuestAssignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
