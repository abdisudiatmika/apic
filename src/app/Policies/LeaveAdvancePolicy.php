<?php

namespace App\Policies;

use App\Models\LeaveAdvance;
use App\Models\User;

class LeaveAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    public function view(User $user, LeaveAdvance $leaveAdvance): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        if ($leaveAdvance->employee->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $leaveAdvance->employee->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['pegawai', 'atasan']);
    }

    public function update(User $user, LeaveAdvance $leaveAdvance): bool
    {
        return false;
    }

    public function delete(User $user, LeaveAdvance $leaveAdvance): bool
    {
        return false;
    }

    public function approveAsAtasan(User $user, LeaveAdvance $leaveAdvance): bool
    {
        return $user->hasRole('atasan')
            && $leaveAdvance->status === 'menunggu_atasan'
            && $leaveAdvance->employee->supervisor_id === $user->employee?->id;
    }

    public function approveAsHr(User $user, LeaveAdvance $leaveAdvance): bool
    {
        return $user->hasAnyRole(['hr', 'administrator'])
            && $leaveAdvance->status === 'menunggu_hr';
    }
}
