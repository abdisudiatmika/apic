<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        if ($leaveRequest->employee->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $leaveRequest->employee->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['pegawai', 'atasan']);
    }

    /**
     * Approve/reject/cancel are exposed as dedicated Filament table actions, not a
     * generic edit form — each checked against one of the methods below rather than
     * a blanket `update`.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return false;
    }

    public function approveAsAtasan(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasRole('atasan')
            && $leaveRequest->status === 'menunggu_atasan'
            && $leaveRequest->employee->supervisor_id === $user->employee?->id;
    }

    public function approveAsHr(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasAnyRole(['hr', 'administrator'])
            && $leaveRequest->status === 'menunggu_hr';
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->employee->user_id === $user->id
            && $leaveRequest->status === 'menunggu_atasan';
    }
}
