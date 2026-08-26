<?php

namespace App\Filament\Portal\Resources\Employees\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nip')->label('NIP'),
                TextEntry::make('name')->label('Nama'),
                TextEntry::make('email')->label('Email')->placeholder('-'),
                TextEntry::make('phone')->label('No. Telepon')->placeholder('-'),
                TextEntry::make('department.name')->label('Departemen')->placeholder('-'),
                TextEntry::make('position.name')->label('Jabatan')->placeholder('-'),
                TextEntry::make('branch.name')->label('Cabang')->placeholder('-'),
                TextEntry::make('supervisor.name')->label('Atasan Langsung')->placeholder('-'),
                TextEntry::make('join_date')->label('Tanggal Mulai Bekerja')->date()->placeholder('-'),
                TextEntry::make('employment_status')->label('Status Pegawai')->badge(),
            ]);
    }
}
