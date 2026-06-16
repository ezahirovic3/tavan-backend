<?php

namespace App\Observers;

use App\Filament\Resources\BrandSuggestions\BrandSuggestionResource;
use App\Models\BrandSuggestion;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class BrandSuggestionObserver
{
    public function created(BrandSuggestion $suggestion): void
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Novi prijedlog brenda')
            ->body('@' . $suggestion->user->username . ' je predložio/la brend "' . $suggestion->name . '"')
            ->icon('heroicon-o-light-bulb')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label('Pregledaj')
                    ->url(BrandSuggestionResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($admins);

        $url     = BrandSuggestionResource::getUrl('index');
        $subject = 'Novi prijedlog brenda: ' . $suggestion->name;
        $html    = '
            <p>Korisnik <strong>@' . e($suggestion->user->username) . '</strong> je predložio/la novi brend:</p>
            <p style="font-size:1.2em;font-weight:bold;">' . e($suggestion->name) . '</p>
            <p><a href="' . $url . '">Pregledaj prijedloge →</a></p>
        ';

        foreach ($admins as $admin) {
            Mail::html($html, function ($message) use ($admin, $subject) {
                $message->to($admin->email)->subject($subject);
            });
        }
    }
}
