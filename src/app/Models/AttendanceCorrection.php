<?php

namespace App\Models;

use App\Concerns\NotifiesApprovers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class AttendanceCorrection extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceCorrectionFactory> */
    use HasFactory, LogsActivity, NotifiesApprovers;

    protected $fillable = [
        'employee_id',
        'date',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'attachment_path',
        'status',
        'atasan_id',
        'atasan_note',
        'atasan_at',
        'hr_id',
        'hr_note',
        'hr_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'atasan_at' => 'datetime',
            'hr_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function notifiableEmployee(): Employee
    {
        return $this->employee;
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_atasan', 'menunggu_hr'], true);
    }

    public function submit(): void
    {
        $this->notifyAtasanOfSubmission(
            'Pengajuan Koreksi Absensi baru',
            "{$this->employee->name} mengajukan koreksi absensi tanggal {$this->date->translatedFormat('d F Y')}."
        );
    }

    public function approveByAtasan(User $user, ?string $note = null): void
    {
        $this->update([
            'status' => 'menunggu_hr',
            'atasan_id' => $user->id,
            'atasan_note' => $note,
            'atasan_at' => now(),
        ]);

        $this->notifyHrOfAtasanApproval(
            'Koreksi Absensi menunggu persetujuan HR',
            "Koreksi absensi {$this->employee->name} ({$this->date->translatedFormat('d F Y')}) sudah disetujui atasan."
        );
    }

    public function rejectByAtasan(User $user, string $note): void
    {
        $this->update([
            'status' => 'ditolak',
            'atasan_id' => $user->id,
            'atasan_note' => $note,
            'atasan_at' => now(),
        ]);

        $this->notifySubmitterOfDecision(
            'Koreksi Absensi ditolak',
            "Pengajuan koreksi absensi Anda tanggal {$this->date->translatedFormat('d F Y')} ditolak atasan: {$note}"
        );
    }

    /**
     * Applies the requested times to attendance_logs. late_minutes is recomputed
     * against the employee's shift for that date if one was scheduled — mirrors
     * the same logic used by the Excel import in Fase 1, just derived from a
     * schedule instead of trusting a source file's own late-minutes column.
     */
    public function approveByHr(User $user, ?string $note = null): void
    {
        $this->update([
            'status' => 'disetujui',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
        ]);

        $lateMinutes = 0;
        $schedule = $this->employee->schedules()->where('date', $this->date->toDateString())->first();

        if ($schedule && $this->requested_check_in) {
            $shift = $schedule->shift;
            $scheduledStart = Carbon::parse($shift->start_time)->addMinutes($shift->tolerance_minutes);
            $actualStart = Carbon::parse($this->requested_check_in);

            if ($actualStart->gt($scheduledStart)) {
                $lateMinutes = $scheduledStart->diffInMinutes($actualStart);
            }
        }

        $status = match (true) {
            $this->requested_check_in === null && $this->requested_check_out === null => 'tidak_hadir',
            $lateMinutes > 0 => 'terlambat',
            default => 'hadir',
        };

        AttendanceLog::updateOrCreate(
            ['employee_id' => $this->employee_id, 'date' => $this->date->toDateString()],
            [
                'check_in' => $this->requested_check_in,
                'check_out' => $this->requested_check_out,
                'late_minutes' => $lateMinutes,
                'status' => $status,
                'source' => 'manual_correction',
            ]
        );

        $this->notifySubmitterOfDecision(
            'Koreksi Absensi disetujui',
            "Koreksi absensi Anda tanggal {$this->date->translatedFormat('d F Y')} telah disetujui dan data absensi diperbarui."
        );
    }

    public function rejectByHr(User $user, string $note): void
    {
        $this->update([
            'status' => 'ditolak',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
        ]);

        $this->notifySubmitterOfDecision(
            'Koreksi Absensi ditolak',
            "Pengajuan koreksi absensi Anda tanggal {$this->date->translatedFormat('d F Y')} ditolak HR: {$note}"
        );
    }

    public function cancel(User $user): void
    {
        $this->update([
            'status' => 'dibatalkan',
            'cancelled_by' => $user->id,
            'cancelled_at' => now(),
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
