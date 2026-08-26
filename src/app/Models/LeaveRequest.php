<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'replacement_employee_id',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:1',
            'atasan_at' => 'datetime',
            'hr_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function replacementEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'replacement_employee_id');
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    /**
     * Displayed as a single "Menunggu" badge in the UI — see the plan's note on
     * simplifying the 5 internal statuses down to the PRD's 4-label vocabulary.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['menunggu_atasan', 'menunggu_hr'], true);
    }

    public function approveByAtasan(User $user, ?string $note = null): void
    {
        $this->update([
            'status' => 'menunggu_hr',
            'atasan_id' => $user->id,
            'atasan_note' => $note,
            'atasan_at' => now(),
        ]);
    }

    public function rejectByAtasan(User $user, string $note): void
    {
        $this->update([
            'status' => 'ditolak',
            'atasan_id' => $user->id,
            'atasan_note' => $note,
            'atasan_at' => now(),
        ]);
    }

    public function approveByHr(User $user, ?string $note = null): void
    {
        $this->update([
            'status' => 'disetujui',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
        ]);
    }

    public function rejectByHr(User $user, string $note): void
    {
        $this->update([
            'status' => 'ditolak',
            'hr_id' => $user->id,
            'hr_note' => $note,
            'hr_at' => now(),
        ]);
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
