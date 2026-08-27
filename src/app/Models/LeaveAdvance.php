<?php

namespace App\Models;

use App\Concerns\NotifiesApprovers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveAdvance extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveAdvanceFactory> */
    use HasFactory, LogsActivity, NotifiesApprovers;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'days',
        'reason',
        'status',
        'atasan_id',
        'atasan_note',
        'atasan_at',
        'hr_id',
        'hr_note',
        'hr_at',
        'outstanding_days',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'decimal:1',
            'outstanding_days' => 'decimal:1',
            'atasan_at' => 'datetime',
            'hr_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_atasan', 'menunggu_hr'], true);
    }

    public function notifiableEmployee(): Employee
    {
        return $this->employee;
    }

    public function submit(): void
    {
        $this->notifyAtasanOfSubmission(
            'Pengajuan Bon Cuti baru',
            "{$this->employee->name} mengajukan Bon Cuti {$this->leaveType->name} ({$this->days} hari)."
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
            'Bon Cuti menunggu persetujuan HR',
            "Bon Cuti {$this->employee->name} ({$this->leaveType->name}, {$this->days} hari) sudah disetujui atasan."
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
            'Bon Cuti ditolak',
            "Pengajuan Bon Cuti {$this->leaveType->name} Anda ditolak atasan: {$note}"
        );
    }

    /**
     * Becomes outstanding debt starting now — LeaveBalanceService deducts it
     * automatically the next time this employee's entitlement for this leave type
     * increases (PRD 5.7).
     */
    public function approveByHr(User $user, ?string $note = null): void
    {
        $this->update([
            'status' => 'disetujui',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
            'outstanding_days' => $this->days,
        ]);

        $this->notifySubmitterOfDecision(
            'Bon Cuti disetujui',
            "Pengajuan Bon Cuti {$this->leaveType->name} Anda ({$this->days} hari) telah disetujui."
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
            'Bon Cuti ditolak',
            "Pengajuan Bon Cuti {$this->leaveType->name} Anda ditolak HR: {$note}"
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
