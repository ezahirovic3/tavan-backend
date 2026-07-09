<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionReminderNotification extends Notification
{
    public function __construct(
        private readonly int $daysRemaining,
        private readonly Carbon $deletionDate,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLabel = $this->daysRemaining === 1
            ? '1 dan'
            : $this->daysRemaining . ' dana';

        return (new MailMessage)
            ->subject("Tavan — Vaš račun se briše za {$daysLabel}")
            ->greeting('Zdravo!')
            ->line("Zatražili ste brisanje vašeg Tavan računa. Ako se ne prijavite, račun i svi podaci će biti trajno obrisani za {$daysLabel} ({$this->deletionDate->translatedFormat('d.m.Y.')}).")
            ->line('Ako ste se predomislili, samo se prijavite u aplikaciju i otkažite brisanje.')
            ->line('Ako ste namjerno zatražili brisanje, ne morate ništa raditi — račun će biti obrisan automatski.')
            ->salutation("Pozdrav,\nTavan tim");
    }
}
