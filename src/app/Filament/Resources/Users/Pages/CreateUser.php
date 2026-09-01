<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    private string $pendingRole;

    /**
     * `role` is a form-only field (not a `users` column) standing in for Spatie's
     * many-to-many roles — captured here and applied in afterCreate(), once the
     * record has an id to attach a pivot row to.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRole = $data['role'];
        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncRoles([$this->pendingRole]);
    }
}
