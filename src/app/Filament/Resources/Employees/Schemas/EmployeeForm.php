<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nip')
                    ->label('NIP / ID Pegawai')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('No. Telepon')
                    ->tel(),
                Select::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('supervisor_id')
                    ->label('Atasan Langsung')
                    ->relationship('supervisor', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('user_id')
                    ->label('Akun Login')
                    ->helperText('Hubungkan ke akun login jika pegawai ini sudah punya akses sistem.')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                DatePicker::make('join_date')
                    ->label('Tanggal Mulai Bekerja')
                    ->native(false),
                Select::make('employment_status')
                    ->label('Status Pegawai')
                    ->options(['tetap' => 'Tetap', 'kontrak' => 'Kontrak', 'probation' => 'Probation'])
                    ->default('kontrak')
                    ->required(),
                TextInput::make('attendance_machine_id')
                    ->label('ID Mesin Absensi')
                    ->helperText('ID pegawai sesuai konfigurasi di mesin/software absensi (PRD 5.2 & 5.3).')
                    ->unique(ignoreRecord: true),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
