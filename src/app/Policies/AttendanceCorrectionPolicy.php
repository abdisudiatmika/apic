<?php

namespace App\Policies;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    public function view(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        if ($attendanceCorrection->employee->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $attendanceCorrection->employee->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['pegawai', 'atasan']);
    }

    public function update(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return false;
    }

    public function delete(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return false;
    }

    public function approveAsAtasan(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->hasRole('atasan')
            && $attendanceCorrection->status === 'menunggu_atasan'
            && $attendanceCorrection->employee->supervisor_id === $user->employee?->id;
    }

    public function approveAsHr(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $user->hasAnyRole(['hr', 'administrator'])
            && $attendanceCorrection->status === 'menunggu_hr';
    }

    public function cancel(User $user, AttendanceCorrection $attendanceCorrection): bool
    {
        return $attendanceCorrection->employee->user_id === $user->id
            && $attendanceCorrection->status === 'menunggu_atasan';
    }
}
