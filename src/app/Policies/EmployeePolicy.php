<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    /**
     * HR/Admin/Direksi see everyone. An atasan may only see their direct reports
     * (plus their own record); a pegawai may only see their own record. This is the
     * enforcement point that must hold even if a Filament UI element is mis-wired —
     * a Pegawai guessing another employee's record URL still gets a 403 here.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        if ($employee->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $employee->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole('administrator');
    }

    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasRole('administrator');
    }

    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->hasRole('administrator');
    }
}
