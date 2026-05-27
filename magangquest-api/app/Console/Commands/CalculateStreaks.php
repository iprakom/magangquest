<?php

namespace App\Console\Commands;

use App\Models\PointTransaction;
use App\Models\QuestAssignment;
use App\Models\Streak;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateStreaks extends Command
{
    protected $signature = 'streaks:calculate';
    protected $description = 'Calculate and update user streaks based on daily quest completions';

    public function handle(): int
    {
        $this->info('Running streak calculation...');
        Log::info('Streak calculation started');

        // Get all active players
        $users = User::where('onboarding_status', User::ONBOARDING_ACTIVE)->get();

        $processed = 0;
        $streaksAwarded = 0;

        foreach ($users as $user) {
            $result = $this->processUserStreak($user);
            $processed++;

            if ($result['streak_updated']) {
                $streaksAwarded++;
            }
        }

        $this->info("Streak calculation completed. Processed: {$processed} users, {$streaksAwarded} streak updates.");
        Log::info("Streak calculation completed. Processed: {$processed}, Updates: {$streaksAwarded}");

        return Command::SUCCESS;
    }

    protected function processUserStreak(User $user): array
    {
        $result = [
            'streak_updated' => false,
            'streak_count' => 0,
            'bonus_awarded' => 0,
        ];

        // Check if user completed at least one quest today
        $completedToday = $this->hasCompletedQuestToday($user);

        // Get or create streak record
        $streak = Streak::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_progress_date' => null,
                'streak_bonus_claimed' => false,
            ]
        );

        if ($completedToday) {
            // Record progress for today
            $previousStreak = $streak->current_streak;
            $streak->recordProgress();

            $result['streak_updated'] = true;
            $result['streak_count'] = $streak->current_streak;

            // Check if streak milestone bonus should be awarded
            $bonus = $this->checkAndAwardStreakBonus($user, $streak, $previousStreak);
            if ($bonus > 0) {
                $result['bonus_awarded'] = $bonus;
            }

            $this->info("User {$user->id}: Streak updated to {$streak->current_streak}");
        } else {
            // Check if streak should be reset (no progress yesterday)
            if ($this->shouldResetStreak($streak)) {
                $previousStreak = $streak->current_streak;
                $streak->resetStreak();

                $this->warn("User {$user->id}: Streak reset from {$previousStreak} to 0");
                Log::info("User {$user->id} streak reset from {$previousStreak} to 0");

                $result['streak_updated'] = true;
                $result['streak_count'] = 0;
            }
        }

        return $result;
    }

    protected function hasCompletedQuestToday(User $user): bool
    {
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return QuestAssignment::where('user_id', $user->id)
            ->where('status', QuestAssignment::STATUS_APPROVED)
            ->whereBetween('validated_at', [$today, $todayEnd])
            ->exists();
    }

    protected function shouldResetStreak(Streak $streak): bool
    {
        if ($streak->current_streak === 0) {
            return false;
        }

        // If no progress date, no reset needed
        if (!$streak->last_progress_date) {
            return false;
        }

        // Check if last progress was before yesterday
        $yesterday = now()->subDay()->startOfDay();
        $lastProgress = $streak->last_progress_date->startOfDay();

        // Reset if last progress was before yesterday (missed at least one day)
        return $lastProgress->lt($yesterday);
    }

    protected function checkAndAwardStreakBonus(User $user, Streak $streak, int $previousStreak): int
    {
        $bonus = 0;
        $currentStreak = $streak->current_streak;

        // Check for newly reached milestones
        if ($currentStreak >= Streak::MILESTONE_7 && $previousStreak < Streak::MILESTONE_7) {
            $bonus += 50;
            $this->info("User {$user->id} reached 7-day streak milestone! Bonus: 50 points");
        }

        if ($currentStreak >= Streak::MILESTONE_14 && $previousStreak < Streak::MILESTONE_14) {
            $bonus += 100;
            $this->info("User {$user->id} reached 14-day streak milestone! Bonus: 100 points");
        }

        if ($currentStreak >= Streak::MILESTONE_21 && $previousStreak < Streak::MILESTONE_21) {
            $bonus += 200;
            $this->info("User {$user->id} reached 21-day streak milestone! Bonus: 200 points");
        }

        if ($currentStreak >= Streak::MILESTONE_30 && $previousStreak < Streak::MILESTONE_30) {
            $bonus += 500;
            $this->info("User {$user->id} reached 30-day streak milestone! Bonus: 500 points");
        }

        // Award bonus if any
        if ($bonus > 0) {
            PointTransaction::createTransaction(
                $user->id,
                $bonus,
                PointTransaction::REF_STREAK_BONUS,
                null,
                null,
                "Streak milestone bonus for {$currentStreak} day streak"
            );

            Log::info("User {$user->id} awarded streak bonus of {$bonus} points for reaching {$currentStreak} days");
        }

        return $bonus;
    }
}
