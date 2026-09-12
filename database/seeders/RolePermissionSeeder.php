<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed the base roles and permissions for the HRM application.
 *
 * This seeder is the single place to define which permissions each role
 * receives. New roles and permissions can be added here or from the admin
 * panel in the future.
 */
class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Permission definitions used by the application.
     *
     * The slug is the value checked by the policies and middleware.
     */
    private const PERMISSIONS = [
        // Employee management
        ['slug' => 'employees.view',       'name' => 'View all employees'],
        ['slug' => 'employees.manage',     'name' => 'Create, update and delete employees'],
        ['slug' => 'employees.view.own',   'name' => 'View own employee profile'],
        ['slug' => 'employees.update.own', 'name' => 'Update own employee profile'],
        ['slug' => 'employees.forceDelete','name' => 'Permanently delete employees'],

        // Leave management
        ['slug' => 'leaves.view',       'name' => 'View all leave requests'],
        ['slug' => 'leaves.manage',     'name' => 'Manage all leave requests'],
        ['slug' => 'leaves.view.own',   'name' => 'View own leave requests'],
        ['slug' => 'leaves.create',     'name' => 'Create a leave request'],
        ['slug' => 'leaves.update.own', 'name' => 'Update own pending/rejected leave request'],
        ['slug' => 'leaves.delete.own', 'name' => 'Delete own pending leave request'],
        ['slug' => 'leaves.approve',    'name' => 'Approve or reject leave requests'],

        // Attendance management
        ['slug' => 'attendance.view',       'name' => 'View all attendance records'],
        ['slug' => 'attendance.manage',     'name' => 'Manage attendance records'],
        ['slug' => 'attendance.view.own',   'name' => 'View own attendance records'],
        ['slug' => 'attendance.create',     'name' => 'Create attendance records'],
        ['slug' => 'attendance.reports',    'name' => 'View attendance reports'],

        // Department management
        ['slug' => 'departments.manage',     'name' => 'Manage departments'],
        ['slug' => 'departments.forceDelete','name' => 'Permanently delete departments'],

        // Admin dashboard
        ['slug' => 'admin.dashboard', 'name' => 'View the advanced admin dashboard'],

        // Activity log
        ['slug' => 'activity-logs.view', 'name' => 'View the activity log'],

        // Settings
        ['slug' => 'settings.manage', 'name' => 'Manage application settings'],

        // Roles & permissions
        ['slug' => 'roles.manage', 'name' => 'Manage roles and permissions'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding roles and permissions...');

        // Create all permissions first
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['slug'], 'guard_name' => 'web'],
            );
        }

        // Define the roles and the permissions they should receive
        $rolePermissions = [
            User::ROLE_SUPER_ADMIN => array_column(self::PERMISSIONS, 'slug'),
            User::ROLE_ADMIN => [
                'employees.view',
                'employees.manage',
                'leaves.view',
                'leaves.manage',
                'leaves.create',
                'leaves.approve',
                'attendance.view',
                'attendance.manage',
                'attendance.create',
                'attendance.reports',
                'departments.manage',
                'admin.dashboard',
                'activity-logs.view',
                'settings.manage',
                'roles.manage',
            ],
            User::ROLE_EMPLOYEE => [
                'employees.view.own',
                'employees.update.own',
                'leaves.view.own',
                'leaves.create',
                'leaves.update.own',
                'leaves.delete.own',
                'attendance.view.own',
                'attendance.create',
            ],
        ];

        foreach ($rolePermissions as $slug => $permissionSlugs) {
            $role = Role::firstOrCreate(
                ['name' => $slug, 'guard_name' => 'web'],
            );

            // Sync the permissions for this role
            $role->syncPermissions($permissionSlugs);

            $this->command->info(sprintf(
                'Role [%s] synced with %d permissions.',
                $role->name,
                count($permissionSlugs)
            ));
        }
    }
}
