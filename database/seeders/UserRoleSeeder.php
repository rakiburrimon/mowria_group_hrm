<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seed the three primary role-based login accounts:
 * super admin, admin and employee.
 *
 * Safe to run multiple times thanks to firstOrCreate.
 */
class UserRoleSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Default password for all seeded accounts.
     * Change this in production before deploying.
     */
    public const DEFAULT_PASSWORD = 'password123';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the HR department exists before assigning employees to it
        $department = Department::firstOrCreate(
            ['name' => 'Human Resources'],
            ['description' => 'HR and administration', 'status' => 'active']
        );

        // Definition of the three role-based seed accounts
        $accounts = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => User::ROLE_SUPER_ADMIN,
                'employee_id' => 'SA001',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => '+1000000000',
                'position' => 'System Administrator',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => User::ROLE_ADMIN,
                'employee_id' => 'ADM001',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'phone' => '+1000000001',
                'position' => 'HR Administrator',
            ],
            [
                'name' => 'Employee User',
                'email' => 'employee@example.com',
                'role' => User::ROLE_EMPLOYEE,
                'employee_id' => 'EMP999',
                'first_name' => 'Employee',
                'last_name' => 'User',
                'phone' => '+1000000002',
                'position' => 'Software Developer',
            ],
        ];

        // Create each user and their linked employee profile
        foreach ($accounts as $account) {
            // Create the user if it does not already exist
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'role' => $account['role'],
                ]
            );

            // Assign the role to the user via Spatie's role system
            $user->assignRole($account['role']);

            // Create the employee profile if it does not already exist
            Employee::firstOrCreate(
                ['employee_id' => $account['employee_id']],
                [
                    'user_id' => $user->id,
                    'department_id' => $department->id,
                    'employee_id' => $account['employee_id'],
                    'first_name' => $account['first_name'],
                    'last_name' => $account['last_name'],
                    'email' => $account['email'],
                    'phone' => $account['phone'],
                    'position' => $account['position'],
                    'hire_date' => now()->subYear(),
                    'salary' => 0,
                    'status' => 'active',
                ]
            );

            // Output the credentials for easy reference
            $this->command->info(sprintf(
                '[%s] %s / %s',
                ucfirst(str_replace('_', ' ', $account['role'])),
                $account['email'],
                self::DEFAULT_PASSWORD
            ));
        }
    }
}
