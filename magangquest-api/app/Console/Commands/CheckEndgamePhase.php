<?php

namespace App\Console\Commands;

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

    // Endgame phase thresholds (in days)
    const CRITICAL_ZONE_DAYS = 10;
    const GRADUATION_DAYS = 0;
    const GRACE_PERIOD_DAYS = 7;
    const FORCE_CLOSE_DAYS = 8;

    public function handle(): int
    {
        $this->info('Running endgame phase check...');
        Log::info('Endgame phase check started');

        $users = User::where('onboarding_status', User::ONBOARDING_ACTIVE)
            ->whereNotNull('end_date')
            ->get();

        $processed = 0;

        foreach ($users as $user) {
            $daysRemaining = $user->getDaysRemaining();
            
            if ($daysRemaining === null) {
                continue;
            }

            $this->processUserEndgamePhase($user, $daysRemaining);
            $processed++;
        }

        $this->info("Endgame check completed. Processed {$processed} users.");
        Log::info("Endgame phase check completed. Processed {$processed} users.");

        return Command::SUCCESS;
    }

    protected function processUserEndgamePhase(User $user, int $daysRemaining): void
    {
        $endDate = $user->end_date;

        // H-10 Critical Zone: When days remaining <= 10 and >= 0
        if ($daysRemaining <= self::CRITICAL_ZONE_DAYS && $daysRemaining > self::GRADUATION_DAYS) {
            $this->enterCriticalZone($user);
        }

        // H-0 Graduation: When days remaining <= 0 (internship has ended)
        if ($daysRemaining <= self::GRADUATION_DAYS && $daysRemaining > -self::GRACE_PERIOD_DAYS) {
            $this->processGraduation($user);
        }

        // H+7 Grace Period: When today is within 7 days after end_date
        $gracePeriodEnd = $endDate->copy()->addDays(self::GRACE_PERIOD_DAYS);
        if (now()->lte($gracePeriodEnd) && $daysRemaining < 0) {
            $this->processGracePeriod($user);
        }

        // H+8 Force Close: When today is 8+ days after end_date
        $forceCloseDate = $endDate->copy()->addDays(self::FORCE_CLOSE_DAYS);
        if (now()->gt($forceCloseDate)) {
            $this->processForceClose($user);
        }
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

    protected function processGraduation(User $user): void
    {
        $user->onboarding_status = User::ONBOARDING_FROZEN;
        $user->is_critical_zone = false;
        $user->save();

        // Award graduation bonus
        $graduationBonus = 100; // configurable
        PointTransaction::createTransaction(
            $user->id,
            $graduationBonus,
            PointTransaction::REF_GRADUATION_BONUS,
            null,
            null,
            'Graduation bonus awarded'
        );

        // Assign graduation badge (log for now - badge system TBD)
        $this->info("User {$user->id} graduated - badge assigned, points frozen");
        Log::info("User {$user->id} graduated - awarded {$graduationBonus} bonus points");

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

    protected function processGracePeriod(User $user): void
    {
        if (!$user->is_grace_period) {
            $user->is_grace_period = true;
            $user->grace_period_started_at = now();
            $user->save();

            $this->info("User {$user->id} entered grace period");
            Log::info("User {$user->id} entered grace period");
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

        $this->warn("User {$user->id} force closed - internship ended");
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
