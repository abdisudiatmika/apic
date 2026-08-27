<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections;

use App\Filament\Portal\Resources\AttendanceCorrections\Pages\CreateAttendanceCorrection;
use App\Filament\Portal\Resources\AttendanceCorrections\Pages\ListAttendanceCorrections;
use App\Filament\Portal\Resources\AttendanceCorrections\Pages\ViewAttendanceCorrection;
use App\Filament\Portal\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionForm;
use App\Filament\Portal\Resources\AttendanceCorrections\Schemas\AttendanceCorrectionInfolist;
use App\Filament\Portal\Resources\AttendanceCorrections\Tables\AttendanceCorrectionsTable;
use App\Models\AttendanceCorrection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceCorrectionResource extends Resource
{
    protected static ?string $model = AttendanceCorrection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Koreksi Absensi';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = parent::getEloquentQuery();

        if ($user->hasRole('atasan') && $employee) {
            return $query->whereHas(
                'employee',
                fn (Builder $q) => $q->where('supervisor_id', $employee->id)->orWhere('id', $employee->id)
            );
        }

        return $query->whereHas('employee', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    public static function form(Schema $schema): Schema
    {
        return AttendanceCorrectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceCorrectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceCorrectionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceCorrections::route('/'),
            'create' => CreateAttendanceCorrection::route('/create'),
            'view' => ViewAttendanceCorrection::route('/{record}'),
        ];
    }
}
