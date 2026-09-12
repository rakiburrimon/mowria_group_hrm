<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\Leave;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            ['name' => 'Information Technology', 'description' => 'IT and software development', 'status' => 'active'],
            ['name' => 'Human Resources', 'description' => 'HR and administration', 'status' => 'active'],
            ['name' => 'Finance', 'description' => 'Accounting and finance', 'status' => 'active'],
            ['name' => 'Marketing', 'description' => 'Marketing and sales', 'status' => 'active'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Create the core roles and permissions before creating users
        $this->call(RolePermissionSeeder::class);

        // Create role-based login accounts (super_admin, admin, employee)
        $this->call(UserRoleSeeder::class);

        // Create test users with employee profiles
        $testUsers = [
            [
                'name' => 'John Doe',
                'email' => 'john.doe@company.com',
                'password' => Hash::make('password123'),
                'employee_data' => [
                    'employee_id' => 'EMP001',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john.doe@company.com',
                    'phone' => '+1234567890',
                    'position' => 'Senior Developer',
                    'department_id' => 1, // IT
                    'hire_date' => '2022-01-15',
                    'salary' => 75000.00,
                    'status' => 'active',
                ]
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
                'password' => Hash::make('password123'),
                'employee_data' => [
                    'employee_id' => 'EMP002',
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                    'email' => 'jane.smith@company.com',
                    'phone' => '+1234567891',
                    'position' => 'HR Manager',
                    'department_id' => 2, // HR
                    'hire_date' => '2021-03-20',
                    'salary' => 65000.00,
                    'status' => 'active',
                ]
            ],
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@company.com',
                'password' => Hash::make('password123'),
                'employee_data' => [
                    'employee_id' => 'EMP003',
                    'first_name' => 'Mike',
                    'last_name' => 'Johnson',
                    'email' => 'mike.johnson@company.com',
                    'phone' => '+1234567892',
                    'position' => 'Accountant',
                    'department_id' => 3, // Finance
                    'hire_date' => '2020-06-10',
                    'salary' => 55000.00,
                    'status' => 'active',
                ]
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@company.com',
                'password' => Hash::make('password123'),
                'employee_data' => [
                    'employee_id' => 'EMP004',
                    'first_name' => 'Sarah',
                    'last_name' => 'Wilson',
                    'email' => 'sarah.wilson@company.com',
                    'phone' => '+1234567893',
                    'position' => 'Marketing Manager',
                    'department_id' => 4, // Marketing
                    'hire_date' => '2023-02-01',
                    'salary' => 60000.00,
                    'status' => 'active',
                ]
            ],
        ];

        foreach ($testUsers as $userData) {
            $employeeData = $userData['employee_data'];
            unset($userData['employee_data']);

            $user = User::create($userData);
            Employee::create(array_merge($employeeData, ['user_id' => $user->id]));
        }

        // Create leave types
        $this->createLeaveTypes();
        
        // Create leave balances for all employees
        $this->createLeaveBalances();
        
        // Create sample attendance records
        $this->createAttendanceRecords();
        
        // Create sample leave records
        $this->createLeaveRecords();
    }

    private function createAttendanceRecords()
    {
        $employees = Employee::all();
        $startDate = Carbon::now()->subDays(30);
        
        foreach ($employees as $employee) {
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                
                // Skip weekends
                if ($date->isWeekend()) {
                    continue;
                }

                // Random attendance status
                $statuses = ['present', 'present', 'present', 'present', 'late', 'absent'];
                $status = $statuses[array_rand($statuses)];
                
                $attendanceData = [
                    'employee_id' => $employee->id,
                    'date' => $date->format('Y-m-d'),
                    'status' => $status,
                ];

                if ($status === 'present' || $status === 'late') {
                    $attendanceData['check_in'] = $status === 'late' ? '09:15' : '08:45';
                    $attendanceData['check_out'] = '17:30';
                }

                Attendance::create($attendanceData);
            }
        }
    }

    private function createLeaveRecords()
    {
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            // Create 1-3 leave requests per employee
            $leaveCount = rand(1, 3);
            
            for ($i = 0; $i < $leaveCount; $i++) {
                $startDate = Carbon::now()->addDays(rand(10, 60))->startOfDay();
                $days = rand(1, 5);
                $endDate = $startDate->copy()->addDays($days - 1);
                
                $types = ['annual', 'sick', 'personal'];
                $statuses = ['approved', 'pending', 'rejected'];
                
                Leave::create([
                    'employee_id' => $employee->id,
                    'type' => $types[array_rand($types)],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                    'reason' => 'Sample leave request',
                    'status' => $statuses[array_rand($statuses)],
                    'approved_by' => 2, // Jane Smith (HR Manager)
                    'remarks' => 'Processed by HR',
                ]);
            }
        }
    }

    private function createLeaveTypes()
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Paid annual leave for employees',
                'max_days_per_year' => 21,
                'requires_approval' => true,
                'allow_carry_over' => true,
                'is_active' => true,
                'color_code' => '#007bff',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Paid sick leave for medical reasons',
                'max_days_per_year' => 10,
                'requires_approval' => false,
                'allow_carry_over' => false,
                'is_active' => true,
                'color_code' => '#28a745',
            ],
            [
                'name' => 'Personal Leave',
                'code' => 'personal',
                'description' => 'Personal leave for personal matters',
                'max_days_per_year' => 5,
                'requires_approval' => true,
                'allow_carry_over' => false,
                'is_active' => true,
                'color_code' => '#ffc107',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => 'Paid maternity leave for new mothers',
                'max_days_per_year' => 90,
                'requires_approval' => true,
                'allow_carry_over' => false,
                'is_active' => true,
                'color_code' => '#e83e8c',
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'description' => 'Paid paternity leave for new fathers',
                'max_days_per_year' => 14,
                'requires_approval' => true,
                'allow_carry_over' => false,
                'is_active' => true,
                'color_code' => '#6f42c1',
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            \App\Models\LeaveType::create($leaveType);
        }
    }

    private function createLeaveBalances()
    {
        $employees = Employee::all();
        $leaveTypes = \App\Models\LeaveType::all();
        $currentYear = Carbon::now()->year;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // Create leave balance for each employee and leave type
                \App\Models\LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType->code,
                    'total_days' => $leaveType->max_days_per_year,
                    'used_days' => 0,
                    'remaining_days' => $leaveType->max_days_per_year,
                    'year' => $currentYear,
                    'carry_over' => 0,
                    'notes' => 'Initial balance for ' . $currentYear,
                ]);
            }
        }
    }
}
