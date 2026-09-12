<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Authorization policy for the Department model.
 *
 * Departments can only be managed by users with the department management permission.
 */
class DepartmentPolicy
{
    /**
     * Determine whether the user can view the department list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can view a specific department.
     */
    public function view(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can create a new department.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can update a department.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can delete a department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can restore a soft-deleted department.
     */
    public function restore(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage');
    }

    /**
     * Determine whether the user can permanently delete a department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.forceDelete');
    }
}
