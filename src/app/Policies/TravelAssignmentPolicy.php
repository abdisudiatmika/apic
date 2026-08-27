<?php

namespace App\Policies;

use App\Models\TravelAssignment;
use App\Models\User;

class TravelAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrator', 'hr', 'direksi', 'atasan', 'pegawai']);
    }

    public function view(User $user, TravelAssignment $travelAssignment): bool
    {
        if ($user->hasAnyRole(['administrator', 'hr', 'direksi'])) {
            return true;
        }

        $requester = $travelAssignment->requester;

        if ($requester->user_id === $user->id) {
            return true;
        }

        if ($travelAssignment->employees->contains('user_id', $user->id)) {
            return true;
        }

        if ($user->hasRole('atasan')) {
            return $requester->supervisor_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['pegawai', 'atasan']);
    }

    public function update(User $user, TravelAssignment $travelAssignment): bool
    {
        return false;
    }

    public function delete(User $user, TravelAssignment $travelAssignment): bool
    {
        return false;
    }

    public function approveAsAtasan(User $user, TravelAssignment $travelAssignment): bool
    {
        return $user->hasRole('atasan')
            && $travelAssignment->status === 'menunggu_atasan'
            && $travelAssignment->requester->supervisor_id === $user->employee?->id;
    }

    public function approveAsHr(User $user, TravelAssignment $travelAssignment): bool
    {
        return $user->hasAnyRole(['hr', 'administrator'])
            && $travelAssignment->status === 'menunggu_hr';
    }

    public function cancel(User $user, TravelAssignment $travelAssignment): bool
    {
        return $travelAssignment->requester->user_id === $user->id
            && $travelAssignment->status === 'menunggu_atasan';
    }

    public function downloadPdf(User $user, TravelAssignment $travelAssignment): bool
    {
        return $travelAssignment->letter_number !== null && $this->view($user, $travelAssignment);
    }
}
