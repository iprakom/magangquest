<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Streak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'current_streak',
        'longest_streak',
        'last_progress_date',
        'streak_bonus_claimed',
        'milestone_7',
        'milestone_14',
        'milestone_21',
        'milestone_30',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_progress_date' => 'date',
        'streak_bonus_claimed' => 'boolean',
        'milestone_7' => 'boolean',
        'milestone_14' => 'boolean',
        'milestone_21' => 'boolean',
        'milestone_30' => 'boolean',
    ];

    // Milestone bonuses (from PRD)
    const MILESTONE_7 = 7;
    const MILESTONE_14 = 14;
    const MILESTONE_21 = 21;
    const MILESTONE_30 = 30;

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public function recordProgress(): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($this->last_progress_date?->toDateString() === $today) {
            // Already recorded progress today
            return;
        }

        if ($this->last_progress_date?->toDateString() === $yesterday) {
            // Consecutive day - increment streak
            $this->current_streak++;
        } else {
            // Streak broken - reset to 1
            $this->current_streak = 1;
        }

        $this->last_progress_date = $today;
        $this->checkMilestones();
        $this->save();
    }

    protected function checkMilestones(): void
    {
        // Check and mark milestones
        if ($this->current_streak >= self::MILESTONE_30 && !$this->milestone_30) {
            $this->milestone_30 = true;
        }
        if ($this->current_streak >= self::MILESTONE_21 && !$this->milestone_21) {
            $this->milestone_21 = true;
        }
        if ($this->current_streak >= self::MILESTONE_14 && !$this->milestone_14) {
            $this->milestone_14 = true;
        }
        if ($this->current_streak >= self::MILESTONE_7 && !$this->milestone_7) {
            $this->milestone_7 = true;
        }

        // Update longest streak
        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }
    }

    public function resetStreak(): void
    {
        $this->current_streak = 0;
        $this->milestone_7 = false;
        $this->milestone_14 = false;
        $this->milestone_21 = false;
        $this->milestone_30 = false;
        $this->save();
    }
}
