<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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

        return $data;
    }
}
