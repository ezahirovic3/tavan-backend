<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserBannedNotification extends Notification
{
    public function __construct(
        private readonly ?Carbon $bannedUntil,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isPermanent = $this->bannedUntil === null || $this->bannedUntil->year >= 2099;

        $mail = (new MailMessage)
            ->from('info@tavan.store', 'Tavan')
            ->subject($isPermanent ? 'Trajna zabrana korištenja TAVAN-a' : 'Privremena zabrana korištenja TAVAN-a')
            ->greeting('Poštovani,')
            ->line('obraćamo vam se povodom vašeg TAVAN profila.')
            ->line('Zaprimili smo više prijava korisnika u vezi s vašim postupanjem prema narudžbama i komunikacijom s kupcima. Nakon uvida u aktivnosti na platformi, uočili smo obrazac ponašanja koji negativno utiče na iskustvo drugih korisnika.');

        if ($isPermanent) {
            $mail->line('Zbog navedenog, vašem profilu je izrečena trajna zabrana korištenja TAVAN-a.');
        } else {
            $days = now()->diffInDays($this->bannedUntil);
            $daysLabel = $days === 1 ? '1 dan' : $days . ' dana';

            $mail->line("Zbog navedenog, vašem profilu je izrečena privremena zabrana korištenja TAVAN-a u trajanju od {$daysLabel}.")
                ->line('Zabrana počinje teći od dana slanja ove obavijesti, nakon čega ćete ponovo moći koristiti svoj profil. Očekujemo da se buduće narudžbe i komunikacija s drugim korisnicima odvijaju korektno i u skladu s pravilima TAVAN-a.');
        }

        return $mail
            ->line('Ukoliko smatrate da je zabrana izrečena greškom ili želite dodatno pojasniti određenu situaciju, slobodno odgovorite na ovaj email.')
            ->salutation("Srdačan pozdrav,\nTAVAN tim");
    }
}
