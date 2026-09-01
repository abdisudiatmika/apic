<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): string => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah kata sandi.'
                        : 'Minimal 8 karakter, kombinasi huruf besar/kecil dan angka.'),
                Select::make('role')
                    ->label('Peran')
                    ->options(fn () => Role::pluck('name', 'name'))
                    ->required()
                    ->helperText('Menentukan panel yang bisa diakses — HR/Administrator/Direksi masuk lewat panel admin, Atasan/Pegawai lewat panel portal.'),
            ]);
    }
}
