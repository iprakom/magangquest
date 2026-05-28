<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quest_id',
        'quest_assignment_id',
        'type',
        'points',
        'balance_after',
        'reference',
        'notes',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    // Reference constants
    const REF_ONBOARDING_BONUS = 'onboarding_bonus';
    const REF_QUEST_APPROVED = 'quest_approved';
    const REF_PROGRESS = 'progress';
    const REF_USULAN_APPROVED = 'usulan_approved';
    const REF_GRADUATION_BONUS = 'graduation_bonus';
    const REF_STREAK_BONUS = 'streak_bonus';
    const REF_REVISE_PENALTY = 'revise_penalty';
    const REF_CANCEL_PENALTY = 'cancel_penalty';
    const REF_HOARDING_PENALTY = 'hoarding_penalty';
    const REF_LATE_PENALTY = 'late_penalty';
    const REF_GRACE_PENALTY = 'grace_penalty';
    const REF_FORCE_CLOSE_PENALTY = 'force_close_penalty';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function questAssignment(): BelongsTo
    {
        return $this->belongsTo(QuestAssignment::class);
    }

    // Helper methods
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    // Static helper to create transaction with running balance
    public static function createTransaction(
        int $userId,
        int $points,
        string $reference,
        ?int $questId = null,
        ?int $questAssignmentId = null,
        ?string $notes = null
    ): self {
        $isCredit = $points >= 0;
        $type = $isCredit ? self::TYPE_CREDIT : self::TYPE_DEBIT;

        // Get current balance
        $lastTransaction = self::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->first();

        $currentBalance = $lastTransaction ? $lastTransaction->balance_after : 0;
        $newBalance = $currentBalance + $points;

        return self::create([
            'user_id' => $userId,
            'quest_id' => $questId,
            'quest_assignment_id' => $questAssignmentId,
            'type' => $type,
            'points' => abs($points),
            'balance_after' => $newBalance,
            'reference' => $reference,
            'notes' => $notes,
        ]);
    }
}
