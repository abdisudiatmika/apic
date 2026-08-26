<?php

namespace App\Policies;

use App\Models\AttendanceLog;
use App\Models\User;

class AttendanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    public function view(User $user, AttendanceLog $attendanceLog): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        $employee = $attendanceLog->employee;

        if ($employee?->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $employee?->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, AttendanceLog $attendanceLog): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, AttendanceLog $attendanceLog): bool
    {
        return $user->hasRole('administrator');
    }
}
