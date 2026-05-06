<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tavan — Reset lozinke')
            ->greeting('Zdravo!')
            ->line('Primili smo zahtjev za reset lozinke na tvom Tavan računu.')
            ->line('Tvoj verifikacijski kod je:')
            ->line('**' . $this->otp . '**')
            ->line('Kod je validan 15 minuta.')
            ->line('Ako nisi ti zatražio/la reset, ignoriši ovaj email.');
    }
}
