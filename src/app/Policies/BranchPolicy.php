<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasRole('administrator');
    }
}
