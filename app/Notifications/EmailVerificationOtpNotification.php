<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{

    public function __construct(private readonly string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tavan — Potvrda email adrese')
            ->greeting('Zdravo!')
            ->line('Hvala što si se registrovao/la na Tavan.')
            ->line('Tvoj verifikacijski kod je:')
            ->line('**' . $this->otp . '**')
            ->line('Kod je validan 15 minuta.')
            ->line('Ako nisi ti kreirao/la ovaj račun, ignoriši ovaj email.');
    }
}
