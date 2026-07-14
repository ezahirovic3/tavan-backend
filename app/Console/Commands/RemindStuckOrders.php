<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderNotificationJob;
use App\Models\Order;
use Illuminate\Console\Command;

class RemindStuckOrders extends Command
{
    protected $signature = 'orders:remind-stuck {--hours=24 : Only remind orders that have been in status for at least this many hours}';

    protected $description = 'Send a push reminder to sellers with accepted orders that still need to be shipped';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        $orders = Order::where('status', 'accepted')
            ->where('delivery_method', '!=', 'pickup')
            ->where('updated_at', '<=', now()->subHours($hours))
            ->get(['id']);

        if ($orders->isEmpty()) {
            $this->info('No stuck orders found.');
            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            SendReminderNotificationJob::dispatch('order', $order->id, 'accepted_seller');
        }

        $this->info("Queued reminders for {$orders->count()} order(s).");
        return self::SUCCESS;
    }
}
