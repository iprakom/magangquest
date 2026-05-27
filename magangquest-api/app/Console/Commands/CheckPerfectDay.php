<?php

namespace App\Console\Commands;

use App\Models\PointTransaction;
use App\Models\QuestAssignment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPerfectDay extends Command
{
    protected $signature = 'perfectday:check';
    protected $description = 'Check and award bonus points for perfect day (3+ quests completed in a day)';

    // Minimum quests to complete for perfect day bonus
    const PERFECT_DAY_THRESHOLD = 3;

    // Bonus points for perfect day
    const PERFECT_DAY_BONUS = 15;

    public function handle(): int
    {
        $this->info('Running perfect day check...');
        Log::info('Perfect day check started');

        // Get all active players
        $users = User::where('onboarding_status', User::ONBOARDING_ACTIVE)->get();

        $processed = 0;
        $perfectDaysAwarded = 0;

        foreach ($users as $user) {
            $result = $this->checkUserPerfectDay($user);

            if ($result['is_perfect_day']) {
                $perfectDaysAwarded++;
            }

            $processed++;
        }

        $this->info("Perfect day check completed. Processed: {$processed} users, {$perfectDaysAwarded} perfect days awarded.");
        Log::info("Perfect day check completed. Processed: {$processed}, Perfect days: {$perfectDaysAwarded}");

        return Command::SUCCESS;
    }

    protected function checkUserPerfectDay(User $user): array
    {
        $result = [
            'is_perfect_day' => false,
            'quests_completed' => 0,
            'bonus_awarded' => 0,
        ];

        // Count quests completed today
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $questsCompleted = QuestAssignment::where('user_id', $user->id)
            ->where('status', QuestAssignment::STATUS_APPROVED)
            ->whereBetween('validated_at', [$today, $todayEnd])
            ->count();

        $result['quests_completed'] = $questsCompleted;

        // Check if user qualifies for perfect day bonus
        if ($questsCompleted >= self::PERFECT_DAY_THRESHOLD) {
            $result['is_perfect_day'] = true;

            // Award perfect day bonus
            PointTransaction::createTransaction(
                $user->id,
                self::PERFECT_DAY_BONUS,
                PointTransaction::REF_PROGRESS,
                null,
                null,
                "Perfect day bonus: completed {$questsCompleted} quests"
            );

            $result['bonus_awarded'] = self::PERFECT_DAY_BONUS;

            $this->info("User {$user->id}: PERFECT DAY! Completed {$questsCompleted} quests, awarded " . self::PERFECT_DAY_BONUS . " bonus points.");
            Log::info("User {$user->id} achieved perfect day with {$questsCompleted} quests", [
                'quests_completed' => $questsCompleted,
                'bonus_awarded' => self::PERFECT_DAY_BONUS,
            ]);
        }

        return $result;
    }
}
