<?php

namespace App\Policies;

use App\Models\AttendanceImport;
use App\Models\User;

class AttendanceImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function view(User $user, AttendanceImport $attendanceImport): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, AttendanceImport $attendanceImport): bool
    {
        return $user->hasRole('administrator');
    }

    public function delete(User $user, AttendanceImport $attendanceImport): bool
    {
        return $user->hasRole('administrator');
    }
}
