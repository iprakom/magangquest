<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use App\Models\PointTransaction;
use App\Models\Quest;
use App\Models\QuestAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEndgamePhase extends Command
{
    protected $signature = 'endgame:check';
    protected $description = 'Check and update user status based on endgame phases relative to end_date';

    // Endgame phase thresholds (in working days)
    const CRITICAL_ZONE_DAYS = 10;
    const GRADUATION_DAYS = 0;
    const GRACE_PERIOD_DAYS = 7;
    const FORCE_CLOSE_DAYS = 8;
    const GRADUATION_BONUS = 200;
    const GRACE_PENALTY_PER_DAY = 10;

    public function handle(): int
    {
        $this->info('Running endgame phase check...');
        Log::info('Endgame phase check started');

        $users = User::where('onboarding_status', User::ONBOARDING_ACTIVE)
            ->whereNotNull('end_date')
            ->get();

        $processed = 0;

        foreach ($users as $user) {
            $workingDaysRemaining = $user->getWorkingDaysRemaining();

            if ($workingDaysRemaining === null) {
                continue;
            }

            $this->processUserEndgamePhase($user, $workingDaysRemaining);
            $processed++;
        }

        // Also process users already in grace period (daily penalty)
        $this->processGracePeriodPenalties();

        $this->info("Endgame check completed. Processed {$processed} users.");
        Log::info("Endgame phase check completed. Processed {$processed} users.");

        return Command::SUCCESS;
    }

    protected function processUserEndgamePhase(User $user, int $workingDaysRemaining): void
    {
        $endDate = $user->end_date;

        // H-10 Critical Zone: When working days remaining <= 10 and >= 0
        if ($workingDaysRemaining <= self::CRITICAL_ZONE_DAYS && $workingDaysRemaining > self::GRADUATION_DAYS) {
            $this->enterCriticalZone($user);
        }

        // H-0 Graduation: When working days remaining <= 0 (internship has ended)
        if ($workingDaysRemaining <= self::GRADUATION_DAYS) {
            // Check if user has any active quests - if so, enter grace period instead of graduating
            $activeQuestsCount = QuestAssignment::where('user_id', $user->id)
                ->whereIn('status', [
                    QuestAssignment::STATUS_ASSIGNED,
                    QuestAssignment::STATUS_ACTIVE,
                    QuestAssignment::STATUS_IN_REVIEW,
                ])
                ->count();

            if ($activeQuestsCount > 0 && !$user->is_grace_period) {
                // Has active quests at H-0 → Enter Grace Period
                $this->enterGracePeriod($user);
            } else {
                // No active quests at H-0 → Graduate normally
                $this->processGraduation($user);
            }
        }

        // H+7 Grace Period: When today is within 7 working days after end_date
        $gracePeriodEnd = $this->getNthWorkingDayAfter($endDate, self::GRACE_PERIOD_DAYS);
        if (now()->lte($gracePeriodEnd) && $workingDaysRemaining < 0) {
            // Grace period is active - ensure flags are set
            if (!$user->is_grace_period) {
                $this->enterGracePeriod($user);
            }
        }

        // H+8 Force Close: When today is 8+ working days after end_date
        $forceCloseDate = $this->getNthWorkingDayAfter($endDate, self::FORCE_CLOSE_DAYS);
        if (now()->gt($forceCloseDate)) {
            $this->processForceClose($user);
        }
    }

    /**
     * Process daily grace period penalties for all users in grace period
     */
    protected function processGracePeriodPenalties(): void
    {
        $gracePeriodUsers = User::where('is_grace_period', true)
            ->where('onboarding_status', '!=', User::ONBOARDING_RESTRICTED)
            ->whereNotNull('grace_period_started_at')
            ->get();

        foreach ($gracePeriodUsers as $user) {
            // Check if penalty already applied today (prevent duplicate)
            $penaltyAppliedToday = PointTransaction::where('user_id', $user->id)
                ->where('reference', PointTransaction::REF_GRACE_PENALTY)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (!$penaltyAppliedToday) {
                $this->applyGracePeriodPenalty($user);
            }
        }
    }

    protected function getNthWorkingDayAfter(\Carbon\Carbon $startDate, int $n): \Carbon\Carbon
    {
        $current = $startDate->copy();
        $count = 0;

        while ($count < $n) {
            $current->addDay();
            if (Holiday::isWorkingDay($current)) {
                $count++;
            }
        }

        return $current;
    }

    protected function enterCriticalZone(User $user): void
    {
        if (!$user->is_critical_zone) {
            $user->is_critical_zone = true;
            $user->save();

            // Accelerate SLAs for pending quests
            $this->accelerateSLAs($user);

            $this->warn("User {$user->id} entered critical zone");
            Log::warning("User {$user->id} entered critical zone - SLAs accelerated");
        }
    }

    protected function accelerateSLAs(User $user): void
    {
        // Get all in_review quests for this user and accelerate their SLA
        $assignments = QuestAssignment::where('user_id', $user->id)
            ->where('status', QuestAssignment::STATUS_IN_REVIEW)
            ->whereNotNull('sla_deadline')
            ->get();

        foreach ($assignments as $assignment) {
            // Reduce SLA deadline by 50% for critical zone
            $originalDeadline = $assignment->sla_deadline;
            $newDeadline = now()->addHours(24); // Simplified: set to 24 hours from now
            $assignment->sla_deadline = $newDeadline;
            $assignment->save();

            $this->info("  Quest {$assignment->id} SLA accelerated from {$originalDeadline} to {$newDeadline}");
            Log::info("Quest {$assignment->id} SLA accelerated for user {$user->id} in critical zone");
        }
    }

    /**
     * Enter grace period - called at H-0 when user has active quests
     */
    protected function enterGracePeriod(User $user): void
    {
        $user->is_grace_period = true;
        $user->grace_period_started_at = now();
        $user->is_critical_zone = false;
        $user->save();

        // Apply initial grace period penalty
        $this->applyGracePeriodPenalty($user);

        $this->warn("User {$user->id} entered GRACE PERIOD - {$user->grace_period_started_at}");
        Log::warning("User {$user->id} entered grace period at H-0 with active quests");

        // Note: We don't freeze quests - user can still work on them during grace period
    }

    /**
     * Apply daily grace period penalty
     */
    protected function applyGracePeriodPenalty(User $user): void
    {
        PointTransaction::createTransaction(
            $user->id,
            -self::GRACE_PENALTY_PER_DAY,
            PointTransaction::REF_GRACE_PENALTY,
            null,
            null,
            'Grace period penalty: -10 poin per day (H+1 to H+7)'
        );

        $this->info("  Applied grace period penalty of -" . self::GRACE_PENALTY_PER_DAY . " points for user {$user->id}");
        Log::info("Grace period penalty applied to user {$user->id}");
    }

    protected function processGraduation(User $user): void
    {
        // Only process if not already in grace period
        if ($user->is_grace_period) {
            return;
        }

        $user->onboarding_status = User::ONBOARDING_FROZEN;
        $user->is_critical_zone = false;
        $user->save();

        // Award graduation bonus
        PointTransaction::createTransaction(
            $user->id,
            self::GRADUATION_BONUS,
            PointTransaction::REF_GRADUATION_BONUS,
            null,
            null,
            'Graduation bonus awarded (no active quests at H-0)'
        );

        $this->info("User {$user->id} graduated - +" . self::GRADUATION_BONUS . " bonus points");
        Log::info("User {$user->id} graduated - awarded " . self::GRADUATION_BONUS . " bonus points");

        // Freeze all active quests
        $this->freezeActiveQuests($user);
    }

    protected function freezeActiveQuests(User $user): void
    {
        $assignments = QuestAssignment::where('user_id', $user->id)
            ->whereIn('status', [
                QuestAssignment::STATUS_ASSIGNED,
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_IN_REVIEW,
            ])
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->status = QuestAssignment::STATUS_CANCELLED;
            $assignment->mentor_notes = 'Quest frozen due to graduation (H-0)';
            $assignment->save();

            $this->info("  Quest {$assignment->id} frozen for user {$user->id}");
            Log::info("Quest {$assignment->id} frozen due to graduation for user {$user->id}");
        }
    }

    protected function processForceClose(User $user): void
    {
        // End internship - mark user as restricted/frozen
        $user->onboarding_status = User::ONBOARDING_RESTRICTED;
        $user->is_grace_period = false;
        $user->is_critical_zone = false;
        $user->save();

        // Close all remaining quests
        $this->closeAllQuests($user);

        // Apply force close penalty if any incomplete quests
        $this->applyForceClosePenalties($user);

        $this->warn("User {$user->id} force closed - internship ended after H+8");
        Log::warning("User {$user->id} force closed after H+8 grace period");
    }

    protected function closeAllQuests(User $user): void
    {
        $assignments = QuestAssignment::where('user_id', $user->id)
            ->whereIn('status', [
                QuestAssignment::STATUS_ASSIGNED,
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_IN_REVIEW,
                QuestAssignment::STATUS_PAUSED,
            ])
            ->get();

        foreach ($assignments as $assignment) {
            $assignment->status = QuestAssignment::STATUS_FAILED;
            $assignment->mentor_notes = 'Quest closed due to force close (H+8)';
            $assignment->save();

            $this->info("  Quest {$assignment->id} closed for user {$user->id}");
            Log::info("Quest {$assignment->id} closed due to force close for user {$user->id}");
        }
    }

    protected function applyForceClosePenalties(User $user): void
    {
        // Count incomplete quests
        $incompleteCount = QuestAssignment::where('user_id', $user->id)
            ->whereIn('status', [
                QuestAssignment::STATUS_ASSIGNED,
                QuestAssignment::STATUS_ACTIVE,
                QuestAssignment::STATUS_IN_REVIEW,
                QuestAssignment::STATUS_PAUSED,
            ])
            ->count();

        if ($incompleteCount > 0) {
            $penaltyPerQuest = 10;
            $totalPenalty = $incompleteCount * $penaltyPerQuest;

            PointTransaction::createTransaction(
                $user->id,
                -$totalPenalty,
                PointTransaction::REF_FORCE_CLOSE_PENALTY,
                null,
                null,
                "Force close penalty: {$incompleteCount} incomplete quests"
            );

            $this->warn("  Applied force close penalty of {$totalPenalty} points for user {$user->id}");
            Log::warning("Force close penalty of {$totalPenalty} points applied to user {$user->id}");
        }
    }
}
