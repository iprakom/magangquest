<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use App\Models\PointTransaction;
use App\Models\Quest;
use App\Models\QuestAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoApproveQuests extends Command
{
    protected $signature = 'quests:auto-approve';
    protected $description = 'Auto-approve quests that have exceeded their SLA deadline';

    // SLA threshold: 3 working days (excluding holidays)
    const SLA_WORKING_DAYS = 3;

    public function handle(): int
    {
        $this->info('Running auto-approve quest check...');
        Log::info('Auto-approve quest check started');

        // Find all in_review assignments where SLA deadline has passed
        $assignments = QuestAssignment::where('status', QuestAssignment::STATUS_IN_REVIEW)
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->with(['user', 'quest'])
            ->get();

        $autoApproved = 0;
        $errors = 0;

        foreach ($assignments as $assignment) {
            try {
                $this->autoApproveQuest($assignment);
                $autoApproved++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to auto-approve quest {$assignment->id}: {$e->getMessage()}");
                Log::error("Auto-approve failed for quest {$assignment->id}", [
                    'error' => $e->getMessage(),
                    'assignment_id' => $assignment->id,
                ]);
            }
        }

        $this->info("Auto-approve check completed. Processed: {$autoApproved} approved, {$errors} errors.");
        Log::info("Auto-approve check completed. Approved: {$autoApproved}, Errors: {$errors}");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function autoApproveQuest(QuestAssignment $assignment): void
    {
        $user = $assignment->user;
        $quest = $assignment->quest;

        // Calculate SLA breach details
        $daysBreached = now()->diffInDays($assignment->sla_deadline);
        $workingDaysBreached = $this->countWorkingDaysBetween(
            $assignment->sla_deadline,
            now()
        );

        // Auto-approve the quest
        $assignment->status = QuestAssignment::STATUS_APPROVED;
        $assignment->validated_at = now();
        $assignment->mentor_notes = "Auto-approved: SLA breached by {$workingDaysBreached} working day(s). Mentor did not review within {$this->getSlaDescription()}.";
        $assignment->save();

        // Award points: fixed 100 points for auto-approved quests
        $points = 100;
        PointTransaction::createTransaction(
            $user->id,
            $points,
            PointTransaction::REF_QUEST_APPROVED,
            $quest->id,
            $assignment->id,
            "Quest auto-approved: SLA breach ({$workingDaysBreached} working days)"
        );

        // Release slot consumption
        $assignment->slot_consumed = 0;
        $assignment->save();

        $this->warn("Auto-approved quest {$assignment->id} for user {$user->id} - awarded {$points} points");
        Log::warning("Quest {$assignment->id} auto-approved for user {$user->id}", [
            'points' => $points,
            'working_days_breached' => $workingDaysBreached,
            'sla_deadline' => $assignment->sla_deadline,
            'validated_at' => now(),
        ]);
    }

    /**
     * Count working days between two dates (excluding weekends and holidays)
     */
    protected function countWorkingDaysBetween($start, $end): int
    {
        if ($start >= $end) {
            return 0;
        }

        return Holiday::countWorkingDays($start, $end);
    }

    /**
     * Get human-readable SLA description
     */
    protected function getSlaDescription(): string
    {
        return self::SLA_WORKING_DAYS . ' working day(s)';
    }

    /**
     * Calculate SLA deadline for a quest submission
     * This is a helper for when quests are submitted (to set sla_deadline)
     */
    public static function calculateSlaDeadline(\DateTimeInterface $submissionDate): \DateTime
    {
        $workingDays = 0;
        $current = $submissionDate;

        while ($workingDays < self::SLA_WORKING_DAYS) {
            $current = Holiday::getNextWorkingDay($current);
            $workingDays++;

            if ($workingDays < self::SLA_WORKING_DAYS) {
                $current = $current->modify('+1 day');
            }
        }

        return $current;
    }
}
