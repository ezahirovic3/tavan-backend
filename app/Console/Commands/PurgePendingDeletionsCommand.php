<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserDeletionService;
use Illuminate\Console\Command;

class PurgePendingDeletionsCommand extends Command
{
    protected $signature = 'users:purge-pending-deletions';

    protected $description = 'Anonymize accounts whose 30-day grace period has expired';

    public function handle(UserDeletionService $deletion): int
    {
        $expired = User::whereNotNull('deletion_requested_at')
            ->where('deletion_requested_at', '<=', now()->subDays(30))
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired deletion requests found.');
            return Command::SUCCESS;
        }

        foreach ($expired as $user) {
            $deletion->anonymize($user);
            $this->info("Anonymized user: {$user->id}");
        }

        $this->info("Purged {$expired->count()} account(s).");

        return Command::SUCCESS;
    }
}
