<?php

namespace App\Concerns;

use App\Models\Employee;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;

/**
 * Persistent (bell-icon) notifications for the four two-stage approval workflows
 * (LeaveRequest, LeaveAdvance, AttendanceCorrection, TravelAssignment) — PRD 11.
 * A model using this must implement notifiableEmployee() to say whose workflow
 * this is, since the "employee" relation is named differently per model
 * (TravelAssignment's requester isn't a 1:1 "employee" column, for instance).
 */
trait NotifiesApprovers
{
    abstract public function notifiableEmployee(): Employee;

    /**
     * Sent when a request is first submitted. Notifies the direct supervisor;
     * if the employee has none (or the supervisor has no login account yet),
     * falls back to HR so the request never gets stuck with no one to notify.
     */
    protected function notifyAtasanOfSubmission(string $title, string $body): void
    {
        $employee = $this->notifiableEmployee();
        $supervisorUser = $employee->supervisor?->user;

        if ($supervisorUser) {
            $this->notifyUser($supervisorUser, $title, $body);
        } else {
            $this->notifyHrUsers($title, $body);
        }
    }

    protected function notifyHrOfAtasanApproval(string $title, string $body): void
    {
        $this->notifyHrUsers($title, $body);
    }

    protected function notifySubmitterOfDecision(string $title, string $body): void
    {
        $user = $this->notifiableEmployee()->user;

        if ($user) {
            $this->notifyUser($user, $title, $body);
        }
    }

    private function notifyHrUsers(string $title, string $body): void
    {
        $hrUsers = User::role(['hr', 'administrator'])->get();

        if ($hrUsers->isNotEmpty()) {
            $this->notifyUser($hrUsers, $title, $body);
        }
    }

    private function notifyUser($recipients, string $title, string $body): void
    {
        FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->sendToDatabase($recipients);
    }
}
