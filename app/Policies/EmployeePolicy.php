<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * Authorization policy for the Employee model.
 *
 * Administrators with the correct permissions can manage employee records.
 * Employees can view and update only their own profile.
 */
class EmployeePolicy
{
    /**
     * Determine whether the user can view the employee list.
     */
    public function viewAny(User $user): bool
    {
        // Only users with the global employee view permission may browse the list
        return $user->hasPermissionTo('employees.view');
    }

    /**
     * Determine whether the user can view a specific employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        // Admins can view anyone; employees can view only their own record
        return $user->hasPermissionTo('employees.view')
            || ($user->hasPermissionTo('employees.view.own') && $user->employee?->id === $employee->id);
    }

    /**
     * Determine whether the user can create new employees.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees.manage');
    }

    /**
     * Determine whether the user can update an employee record.
     */
    public function update(User $user, Employee $employee): bool
    {
        // Admins can edit any record; employees can edit only their own
        return $user->hasPermissionTo('employees.manage')
            || ($user->hasPermissionTo('employees.update.own') && $user->employee?->id === $employee->id);
    }

    /**
     * Determine whether the user can delete an employee record.
     */
    public function delete(User $user, Employee $employee): bool
    {
        // Soft delete is part of employee management
        return $user->hasPermissionTo('employees.manage');
    }

    /**
     * Determine whether the user can restore a soft-deleted employee.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees.manage');
    }

    /**
     * Determine whether the user can permanently delete an employee.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        // Permanent deletion is a separate, more dangerous permission
        return $user->hasPermissionTo('employees.forceDelete');
    }
}
