<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

/**
 * Authorization policy for the Attendance model.
 *
 * Employees can view and create their own attendance records.
 * Administrators with attendance permissions can manage and report on all records.
 */
class AttendancePolicy
{
    /**
     * Determine whether the user can view attendance records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendance.view')
            || $user->hasPermissionTo('attendance.view.own');
    }

    /**
     * Determine whether the user can view a specific attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('attendance.view')
            || ($user->hasPermissionTo('attendance.view.own') && $user->employee?->id === $attendance->employee_id);
    }

    /**
     * Determine whether the user can create attendance records.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('attendance.create');
    }

    /**
     * Determine whether the user can update an attendance record.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Attendance corrections are restricted to administrators
        return $user->hasPermissionTo('attendance.manage');
    }

    /**
     * Determine whether the user can delete an attendance record.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('attendance.manage');
    }

    /**
     * Determine whether the user can view attendance reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermissionTo('attendance.reports');
    }

    /**
     * Determine whether the user can restore a soft-deleted attendance record.
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('attendance.manage');
    }

    /**
     * Determine whether the user can permanently delete an attendance record.
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('attendance.manage');
    }
}
