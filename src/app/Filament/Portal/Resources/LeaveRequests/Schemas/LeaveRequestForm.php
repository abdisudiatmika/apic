<?php

namespace App\Filament\Portal\Resources\LeaveRequests\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

/**
 * Only the fields a pegawai actually fills in. employee_id, days, and status are
 * computed/injected in Pages\CreateLeaveRequest — never exposed here, so a request
 * can't be submitted "on behalf of" someone else or pre-set to an approved status.
 */
class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name', fn ($query) => $query->where('is_active', true))
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->native(false)
                    ->required()
                    ->live(),
                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->native(false)
                    ->required()
                    ->minDate(fn ($get) => $get('start_date'))
                    ->live(),
                Textarea::make('reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
                Select::make('replacement_employee_id')
                    ->label('Pegawai Pengganti / PIC (opsional)')
                    ->options(fn () => Employee::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                FileUpload::make('attachment_path')
                    ->label('Lampiran (opsional)')
                    ->disk('local')
                    ->directory('leave-attachments')
                    ->visibility('private')
                    ->maxSize(5120),
            ]);
    }
}
