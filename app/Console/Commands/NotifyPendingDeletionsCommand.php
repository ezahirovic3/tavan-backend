<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AccountDeletionReminderNotification;
use Illuminate\Console\Command;

class NotifyPendingDeletionsCommand extends Command
{
    protected $signature = 'users:notify-pending-deletions';

    protected $description = 'Email reminders to users whose account will be purged soon (7 days and 1 day before the 30-day grace period ends)';

    /**
     * Maps "days since deletion was requested" => "days remaining until purge",
     * since accounts are anonymized 30 days after deletion_requested_at.
     */
    private const REMINDER_SCHEDULE = [
        23 => 7,
        29 => 1,
    ];

    public function handle(): int
    {
        $sent = 0;

        foreach (self::REMINDER_SCHEDULE as $daysSinceRequest => $daysRemaining) {
            $targetDate = now()->subDays($daysSinceRequest)->toDateString();

            $users = User::whereNotNull('deletion_requested_at')
                ->whereDate('deletion_requested_at', $targetDate)
                ->get();

            foreach ($users as $user) {
                $deletionDate = $user->deletion_requested_at->copy()->addDays(30);

                $user->notify(new AccountDeletionReminderNotification($daysRemaining, $deletionDate));

                $this->info("Reminder ({$daysRemaining}d) sent to user: {$user->id}");
                $sent++;
            }
        }

        if ($sent === 0) {
            $this->info('No pending deletions due for a reminder today.');
        } else {
            $this->info("Sent {$sent} reminder(s).");
        }

        return Command::SUCCESS;
    }
}
