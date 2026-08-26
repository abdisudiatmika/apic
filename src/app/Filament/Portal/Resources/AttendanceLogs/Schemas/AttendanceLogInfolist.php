<?php

namespace App\Filament\Portal\Resources\AttendanceLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.name')->label('Pegawai'),
                TextEntry::make('date')->label('Tanggal')->date(),
                TextEntry::make('check_in')->label('Jam Masuk')->time()->placeholder('-'),
                TextEntry::make('check_out')->label('Jam Keluar')->time()->placeholder('-'),
                TextEntry::make('late_minutes')->label('Telat (menit)')->numeric(),
                TextEntry::make('early_leave_minutes')->label('Pulang Awal (menit)')->numeric(),
                TextEntry::make('status')->label('Status')->badge(),
            ]);
    }
}
