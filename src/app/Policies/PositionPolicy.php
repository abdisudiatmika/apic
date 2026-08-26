<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function view(User $user, Position $position): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasRole('administrator');
    }
}
