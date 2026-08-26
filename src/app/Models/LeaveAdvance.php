<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveAdvance extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveAdvanceFactory> */
    use HasFactory, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logFillable();
    }
}
