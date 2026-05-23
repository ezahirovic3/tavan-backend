<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserDeletionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelDeletion')
                ->label('Poništi brisanje')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('success')
                ->visible(fn () => auth()->user()->isSuperAdmin() && $this->record->deletion_requested_at)
                ->requiresConfirmation()
                ->modalHeading('Poništi brisanje računa')
                ->modalDescription('Datum brisanja će biti uklonjen i račun postaje aktivan.')
                ->action(function () {
                    $this->record->update(['deletion_requested_at' => null]);
                    Notification::make()->success()->title('Brisanje poništeno')->send();
                    $this->fillForm();
                }),

            Action::make('forceAnonymize')
                ->label('Odmah obriši')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->visible(fn () => auth()->user()->isSuperAdmin() && ! UserResource::isLocked($this->record))
                ->requiresConfirmation()
                ->modalHeading('Trajno obriši račun')
                ->modalDescription('Ovo će odmah anonimizirati ovaj račun i obrisati sve podatke. Akcija je nepovratna.')
                ->action(function (UserDeletionService $deletion) {
                    $deletion->anonymize($this->record);
                    Notification::make()->success()->title('Račun je trajno obrisan')->send();
                    return redirect(UserResource::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Self-demotion safety: don't allow lowering own role.
        if ($this->record->id === auth()->id()) {
            unset($data['role']);
        }

        // super_admin guard: never accept this value from the panel.
        if (($data['role'] ?? null) === 'super_admin') {
            unset($data['role']);
        }

        // deletion_requested_at can only be set by super_admin via the visible field.
        if (! auth()->user()->isSuperAdmin()) {
            unset($data['deletion_requested_at']);
        }

        return $data;
    }
}
