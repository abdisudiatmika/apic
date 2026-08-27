<?php

namespace App\Models;

use App\Concerns\NotifiesApprovers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class TravelAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\TravelAssignmentFactory> */
    use HasFactory, LogsActivity, NotifiesApprovers;

    protected $fillable = [
        'requested_by',
        'type',
        'destination',
        'start_date',
        'end_date',
        'purpose',
        'transportation',
        'estimated_cost',
        'attachment_path',
        'letter_number',
        'signatory_name',
        'signatory_position',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'estimated_cost' => 'decimal:2',
            'atasan_at' => 'datetime',
            'hr_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'travel_assignment_employees');
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
        return $this->requester;
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_atasan', 'menunggu_hr'], true);
    }

    public function submit(): void
    {
        $this->notifyAtasanOfSubmission(
            'Pengajuan Surat Tugas baru',
            "{$this->requester->name} mengajukan {$this->typeLabel()} ke {$this->destination}."
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
            'Surat Tugas menunggu persetujuan HR',
            "{$this->typeLabel()} {$this->requester->name} ke {$this->destination} sudah disetujui atasan."
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
            'Surat Tugas ditolak',
            "Pengajuan {$this->typeLabel()} Anda ke {$this->destination} ditolak atasan: {$note}"
        );
    }

    /**
     * Final approval: numbers the letter and syncs attendance_logs to 'dinas' for
     * every assigned employee across the date range, so they don't read as absent
     * (PRD 5.10 "Integrasi status dengan absensi"). Never overwrites an existing
     * hadir/terlambat log — dinas only fills a gap, it doesn't erase real attendance.
     */
    public function approveByHr(User $user, ?string $note = null, ?string $signatoryName = null, ?string $signatoryPosition = null): void
    {
        $this->update([
            'status' => 'disetujui',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
            'letter_number' => $this->generateLetterNumber(),
            'signatory_name' => $signatoryName,
            'signatory_position' => $signatoryPosition,
        ]);

        $cursor = $this->start_date->copy();

        while ($cursor->lte($this->end_date)) {
            foreach ($this->employees as $employee) {
                $existing = AttendanceLog::where('employee_id', $employee->id)
                    ->where('date', $cursor->toDateString())
                    ->first();

                if ($existing && in_array($existing->status, ['hadir', 'terlambat'], true)) {
                    continue;
                }

                AttendanceLog::updateOrCreate(
                    ['employee_id' => $employee->id, 'date' => $cursor->toDateString()],
                    ['status' => 'dinas', 'source' => 'travel_assignment']
                );
            }

            $cursor->addDay();
        }

        $this->notifySubmitterOfDecision(
            'Surat Tugas disetujui',
            "{$this->typeLabel()} Anda ke {$this->destination} disetujui. Nomor surat: {$this->letter_number}."
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
            'Surat Tugas ditolak',
            "Pengajuan {$this->typeLabel()} Anda ke {$this->destination} ditolak HR: {$note}"
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

    public function typeLabel(): string
    {
        return match ($this->type) {
            'surat_tugas' => 'Surat Tugas',
            'perjalanan_dinas' => 'Perjalanan Dinas',
            'surat_jalan' => 'Surat Jalan',
            default => $this->type,
        };
    }

    /**
     * {urut}/ST-APIC/{bulan-romawi}/{tahun}. Placeholder scheme — PRD 5.10 itself
     * flags that numbering format needs to follow the company's actual SOP.
     */
    private function generateLetterNumber(): string
    {
        $year = now()->year;
        $sequence = static::whereNotNull('letter_number')
            ->whereYear('hr_at', $year)
            ->count() + 1;

        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $roman = $romanMonths[now()->month - 1];

        return sprintf('%03d/ST-APIC/%s/%d', $sequence, $roman, $year);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
