<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

/**
 * Authorization policy for the Leave model.
 *
 * Employees can manage their own leave requests within the limits set by the role.
 * Administrators with leave management permissions can handle any request.
 */
class LeavePolicy
{
    /**
     * Determine whether the user can view the list of leave requests.
     */
    public function viewAny(User $user): bool
    {
        // Admins see all; employees see their own list
        return $user->hasPermissionTo('leaves.view')
            || $user->hasPermissionTo('leaves.view.own');
    }

    /**
     * Determine whether the user can view a specific leave request.
     */
    public function view(User $user, Leave $leave): bool
    {
        return $user->hasPermissionTo('leaves.view')
            || ($user->hasPermissionTo('leaves.view.own') && $user->employee?->id === $leave->employee_id);
    }

    /**
     * Determine whether the user can create a new leave request.
     */
    public function create(User $user): bool
    {
        // Both employees and admins can apply for leave
        return $user->hasPermissionTo('leaves.create');
    }

    /**
     * Determine whether the user can update a leave request.
     */
    public function update(User $user, Leave $leave): bool
    {
        // Admins can edit any request; employees can edit their own pending or rejected requests
        return $user->hasPermissionTo('leaves.manage')
            || (
                $user->hasPermissionTo('leaves.update.own') &&
                $user->employee?->id === $leave->employee_id &&
                in_array($leave->status, ['pending', 'rejected'], true)
            );
    }

    /**
     * Determine whether the user can delete a leave request.
     */
    public function delete(User $user, Leave $leave): bool
    {
        // Admins can delete any request; employees can withdraw their own pending request
        return $user->hasPermissionTo('leaves.manage')
            || (
                $user->hasPermissionTo('leaves.delete.own') &&
                $user->employee?->id === $leave->employee_id &&
                $leave->status === 'pending'
            );
    }

    /**
     * Determine whether the user can approve or reject a leave request.
     */
    public function approve(User $user, Leave $leave): bool
    {
        return $user->hasPermissionTo('leaves.approve');
    }

    /**
     * Determine whether the user can restore a soft-deleted leave request.
     */
    public function restore(User $user, Leave $leave): bool
    {
        return $user->hasPermissionTo('leaves.manage');
    }

    /**
     * Determine whether the user can permanently delete a leave request.
     */
    public function forceDelete(User $user, Leave $leave): bool
    {
        return $user->hasPermissionTo('leaves.manage');
    }
}
