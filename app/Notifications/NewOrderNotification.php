<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Backs up the "new order" push notification for sellers who miss it
 * (push disabled, phone off, app killed, etc).
 */
class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $buyerName,
        private readonly ?string $productTitle,
        private readonly int $itemCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $summary = $this->itemCount > 1
            ? "{$this->itemCount} artikala"
            : '"' . $this->productTitle . '"';

        return (new MailMessage)
            ->subject('Nova narudžba! 🛍️')
            ->greeting('Zdravo' . ($notifiable->name ? ', ' . $notifiable->name : '') . '!')
            ->line("{$this->buyerName} je naručio/la {$summary}.")
            ->line('Ukupan iznos narudžbe: ' . number_format((float) $this->order->total, 2) . ' KM.')
            ->line('Molimo prihvatite ili odbijte narudžbu u najkraćem roku u aplikaciji.')
            ->salutation("Pozdrav,\nTavan tim");
    }
}
