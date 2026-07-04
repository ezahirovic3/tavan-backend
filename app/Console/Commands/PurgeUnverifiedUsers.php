<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup of stale unverified accounts (pre-1.2.0 the phone
 * verification hard-lock drove users away mid-onboarding). Run manually,
 * always with --dry-run first. Not scheduled on purpose.
 */
class PurgeUnverifiedUsers extends Command
{
    protected $signature = 'users:purge-unverified
                            {--before= : Only accounts created before this date (YYYY-MM-DD), required}
                            {--dry-run : List matching accounts without deleting anything}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete accounts that never verified email or phone, created before a given date';

    public function handle(ImageService $images): int
    {
        $before = $this->option('before');

        if (! $before) {
            $this->error('The --before=YYYY-MM-DD option is required.');
            return self::FAILURE;
        }

        try {
            $cutoff = Carbon::parse($before)->startOfDay();
        } catch (\Throwable) {
            $this->error("Could not parse date: {$before}");
            return self::FAILURE;
        }

        $query = User::query()
            ->where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->whereNull('email_verified_at')->orWhereNull('phone_verified_at'))
            // Never touch staff or the system support account
            ->where('is_system', false)
            ->where(fn ($q) => $q->whereNull('role')->orWhere('role', 'user'))
            // Belt and braces: gating should make marketplace activity impossible
            // for unverified users, but never delete anyone who has any anyway.
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('products')->whereColumn('products.seller_id', 'users.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.buyer_id', 'users.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->whereColumn('orders.seller_id', 'users.id'))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('messages')->whereColumn('messages.sender_id', 'users.id'));

        $matches = $query->orderBy('created_at')->get([
            'id', 'email', 'username', 'avatar', 'email_verified_at', 'phone_verified_at', 'created_at',
        ]);

        if ($matches->isEmpty()) {
            $this->info("No unverified accounts created before {$cutoff->toDateString()}.");
            return self::SUCCESS;
        }

        $this->table(
            ['Email', 'Username', 'Created', 'Missing'],
            $matches->map(fn (User $u) => [
                $u->email,
                $u->username,
                $u->created_at->toDateString(),
                trim(($u->email_verified_at ? '' : 'email ').($u->phone_verified_at ? '' : 'phone')),
            ])
        );

        $this->info("{$matches->count()} account(s) match (created before {$cutoff->toDateString()}).");

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Permanently delete these {$matches->count()} account(s)?")) {
            $this->comment('Aborted.');
            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($matches as $user) {
            if ($user->avatar) {
                $images->deleteByUrl($user->avatar);
            }
            $user->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} account(s). Related rows removed via FK cascades.");

        return self::SUCCESS;
    }
}
