<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup for the bug fixed in NotifyFollowersOfNewListingsCommand:
 * that command mass-updated products via Product::whereIn(...)->update(...),
 * which goes through Eloquent's Builder and auto-injects updated_at on every
 * mass update — so it silently reset updated_at (not just
 * followers_notified_at) on every product it processed.
 *
 * Because followers_notified_at was added as a nullable column with no
 * backfill (migration 2026_08_20_120001), the *entire* pre-existing active
 * catalog looked "pending" to that command on its first few runs after
 * deploy — so this isn't limited to new listings, it clobbered updated_at
 * across the whole marketplace. Since the feed/search default sort is
 * updated_at DESC (ProductController::index), that scrambled "newest"
 * ordering broadly: old listings jumped to the top, genuinely new ones got
 * buried past page 1.
 *
 * We can't recover the true prior updated_at (it's overwritten), so this
 * resets it to created_at for every row that was touched by the bug —
 * the safest available floor, and consistent with what "newest" should mean.
 * Any product whose followers_notified_at is set was touched by that command
 * at least once, so that's the target set. --dry-run previews the affected
 * count with no side effects. One-off: not scheduled, run manually once.
 */
class FixCorruptedUpdatedAtCommand extends Command
{
    protected $signature = 'products:fix-corrupted-updated-at {--dry-run : Show affected count without changing anything}';

    protected $description = 'Reset updated_at back to created_at for products whose updated_at was clobbered by the NotifyFollowersOfNewListingsCommand bug';

    public function handle(): int
    {
        $affected = Product::whereNotNull('followers_notified_at')
            ->whereColumn('updated_at', '!=', 'created_at')
            ->get(['id', 'title', 'seller_id', 'created_at', 'updated_at']);

        if ($affected->isEmpty()) {
            $this->info('No corrupted rows found.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'title', 'seller_id', 'created_at', 'updated_at (corrupted)'],
            $affected->take(20)->map(fn (Product $p) => [
                $p->id, $p->title, $p->seller_id, $p->created_at, $p->updated_at,
            ])->all()
        );

        if ($affected->count() > 20) {
            $this->line('... and ' . ($affected->count() - 20) . ' more.');
        }

        if ($this->option('dry-run')) {
            $this->info("{$affected->count()} product(s) would have updated_at reset to created_at. Re-run without --dry-run to apply.");

            return self::SUCCESS;
        }

        // Raw query builder — Product::whereIn(...)->update() would re-trigger
        // Eloquent's auto updated_at injection (it'd just overwrite our explicit
        // value with now(), same bug this command exists to undo).
        DB::table('products')
            ->whereNotNull('followers_notified_at')
            ->whereColumn('updated_at', '!=', 'created_at')
            ->update(['updated_at' => DB::raw('created_at')]);

        $this->info("{$affected->count()} product(s) had updated_at reset to created_at.");

        return self::SUCCESS;
    }
}
