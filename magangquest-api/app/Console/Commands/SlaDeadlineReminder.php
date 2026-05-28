<?php

namespace App\Console\Commands;

use App\Models\QuestAssignment;
use App\Models\User;
use App\Mail\SlaDeadlineReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SlaDeadlineReminder extends Command
{
    protected $signature = 'quests:sla-reminder';
    protected $description = 'Send reminders to mentors when quest SLA is approaching (24 hours remaining)';

    // Reminder threshold: 24 hours before SLA deadline
    const REMINDER_HOURS = 24;

    public function handle(): int
    {
        $this->info('Running SLA deadline reminder check...');
        Log::info('SLA deadline reminder check started');

        // Find all in_review assignments where SLA deadline is within 24 hours
        $reminderThreshold = now()->addHours(self::REMINDER_HOURS);
        $assignments = QuestAssignment::where('status', QuestAssignment::STATUS_IN_REVIEW)
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<=', $reminderThreshold)
            ->where('sla_deadline', '>', now())
            ->with(['user', 'quest'])
            ->get();

        $remindersSent = 0;
        $errors = 0;

        foreach ($assignments as $assignment) {
            try {
                $this->sendReminder($assignment);
                $remindersSent++;
            } catch (\Exception $e) {
                $errors++;
                $this->error("Failed to send SLA reminder for quest {$assignment->id}: {$e->getMessage()}");
                Log::error("SLA reminder failed for quest {$assignment->id}", [
                    'error' => $e->getMessage(),
                    'assignment_id' => $assignment->id,
                ]);
            }
        }

        $this->info("SLA reminder check completed. Sent: {$remindersSent}, Errors: {$errors}");
        Log::info("SLA reminder check completed. Sent: {$remindersSent}, Errors: {$errors}");

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function sendReminder(QuestAssignment $assignment): void
    {
        $intern = $assignment->user;
        $mentor = User::find($intern->mentor_id);

        if (!$mentor) {
            $this->warn("No mentor found for intern {$intern->id}");
            return;
        }

        // Calculate hours remaining
        $hoursRemaining = now()->diffInHours($assignment->sla_deadline);

        Mail::to($mentor->email)->send(new SlaDeadlineReminderMail(
            $assignment->quest->title,
            $intern->name,
            "{$hoursRemaining} jam",
            $assignment->sla_deadline->toDateTimeString()
        ));

        $this->warn("SLA reminder sent for quest {$assignment->id} to mentor {$mentor->email}");
        Log::info("SLA reminder sent for quest {$assignment->id}", [
            'quest_id' => $assignment->quest_id,
            'intern_id' => $intern->id,
            'mentor_id' => $mentor->id,
            'sla_deadline' => $assignment->sla_deadline,
            'hours_remaining' => $hoursRemaining,
        ]);
    }
}
