<?php

namespace App\Policies;

use App\Models\LeaveBlackoutDate;
use App\Models\User;

class LeaveBlackoutDatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function view(User $user, LeaveBlackoutDate $leaveBlackoutDate): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function update(User $user, LeaveBlackoutDate $leaveBlackoutDate): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }

    public function delete(User $user, LeaveBlackoutDate $leaveBlackoutDate): bool
    {
        return $user->hasAnyRole(['administrator', 'hr']);
    }
}
