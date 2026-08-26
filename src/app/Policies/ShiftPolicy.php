<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function view(User $user, Shift $shift): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, Shift $shift): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, Shift $shift): bool
    {
        return $user->hasRole('administrator');
    }
}
