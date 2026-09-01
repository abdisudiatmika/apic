<?php

namespace App\Policies;

use App\Models\User;

/**
 * Stricter than EmployeePolicy on purpose: managing login accounts and roles is
 * the most sensitive operation in this system (PRD bagian 2 scopes "Mengatur
 * user, hak akses" to Administrator specifically, not HR).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('administrator');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrator');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasRole('administrator');
    }

    /**
     * An administrator can't delete their own account — the only guard against
     * locking everyone out of Kelola User entirely by accident.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('administrator') && $user->id !== $model->id;
    }
}
