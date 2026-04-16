<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Leave;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LeaveModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Leave Module...');
        
        // Create leave types
        $this->createLeaveTypes();
        
        // Create leave balances for all employees
        $this->createLeaveBalances();
        
        // Create sample leave records
        $this->createLeaveRecords();
        
        $this->command->info('Leave Module seeded successfully!');
    }

    private function createLeaveTypes()
    {
        $this->command->info('Creating leave types...');
        
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
            LeaveType::create($leaveType);
        }
        
        $this->command->info('Leave types created successfully!');
    }

    private function createLeaveBalances()
    {
        $this->command->info('Creating leave balances...');
        
        $employees = Employee::all();
        $leaveTypes = LeaveType::all();
        $currentYear = Carbon::now()->year;

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // Create leave balance for each employee and leave type
                LeaveBalance::create([
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
        
        $this->command->info('Leave balances created successfully!');
    }

    private function createLeaveRecords()
    {
        $this->command->info('Creating sample leave records...');
        
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
        
        $this->command->info('Sample leave records created successfully!');
    }
}
