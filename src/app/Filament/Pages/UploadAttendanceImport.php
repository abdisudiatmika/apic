<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessAttendanceImport;
use App\Models\AttendanceImport;
use App\Models\Branch;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class UploadAttendanceImport extends Page
{
    protected string $view = 'filament.pages.upload-attendance-import';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'Import Absensi';

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 2;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', AttendanceImport::class) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->helperText('File "Exception Stat." tidak mencantumkan cabang, jadi pilih cabang yang sesuai dengan mesin absensi ini. ID mesin absensi dicocokkan hanya terhadap pegawai di cabang ini.')
                    ->options(fn () => Branch::pluck('name', 'id'))
                    ->required(),
                FileUpload::make('file')
                    ->label('File Excel Absensi (.xls / .xlsx)')
                    ->disk('local')
                    ->directory('attendance-imports')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $attendanceImport = AttendanceImport::create([
            'file_name' => basename($data['file']),
            'uploaded_by' => auth()->id(),
            'status' => 'processing',
        ]);

        ProcessAttendanceImport::dispatch(
            $attendanceImport->id,
            $data['file'],
            (int) $data['branch_id'],
        );

        $this->form->fill();

        Notification::make()
            ->title('File diterima, sedang diproses')
            ->body('Cek "Riwayat Import" untuk melihat hasilnya dalam beberapa saat.')
            ->success()
            ->send();
    }
}
