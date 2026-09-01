<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    private string $pendingRole;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * `role` isn't a `users` column — fill the form's pseudo-field from Spatie's
     * roles relation instead of the record's own attributes.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->getRoleNames()->first();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingRole = $data['role'];
        unset($data['role']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles([$this->pendingRole]);
    }
}
