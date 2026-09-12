<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardService
{
    protected EmployeeRepositoryInterface $employeeRepository;
    protected AttendanceRepositoryInterface $attendanceRepository;
    protected LeaveRepositoryInterface $leaveRepository;
    protected DepartmentRepositoryInterface $departmentRepository;

    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        AttendanceRepositoryInterface $attendanceRepository,
        LeaveRepositoryInterface $leaveRepository,
        DepartmentRepositoryInterface $departmentRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->leaveRepository = $leaveRepository;
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * Get private dashboard data for logged-in user
     */
    public function getPrivateDashboard(Employee $employee): array
    {
        $cacheKey = "private_dashboard_{$employee->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($employee) {
            return [
                'user_info' => $this->getUserInfo($employee),
                'attendance_summary' => $this->getAttendanceSummary($employee),
                'leave_balance' => $this->getLeaveBalance($employee),
                'recent_activities' => $this->getRecentActivities($employee),
            ];
        });
    }

    /**
     * Get advanced dashboard data for admin/HR
     */
    public function getAdvancedDashboard(): array
    {
        $cacheKey = 'advanced_dashboard';
        
        return Cache::remember($cacheKey, 600, function () {
            return [
                'total_employees' => $this->getTotalEmployees(),
                'active_inactive_employees' => $this->getActiveInactiveEmployees(),
                'attendance_summary' => $this->getAttendanceSummaryAll(),
                'leave_statistics' => $this->getLeaveStatistics(),
                'department_breakdown' => $this->getDepartmentBreakdown(),
            ];
        });
    }

    /**
     * Get attendance analytics for graphs
     */
    public function getAttendanceAnalytics(string $period = 'month'): array
    {
        $cacheKey = "attendance_analytics_{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();

            $attendances = $this->attendanceRepository->byDateRange($startDate, $endDate);

            return [
                'labels' => $this->generateDateLabels($startDate, $endDate, $period),
                'datasets' => $this->formatAttendanceDatasets($attendances, $startDate, $endDate, $period),
                'summary' => $this->attendanceRepository->getAttendanceStats($startDate, $endDate),
            ];
        });
    }

    /**
     * Get leave analytics for graphs
     */
    public function getLeaveAnalytics(string $period = 'month'): array
    {
        $cacheKey = "leave_analytics_{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($period) {
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();

            $leaves = $this->leaveRepository->byDateRange($startDate, $endDate);

            return [
                'labels' => $this->generateDateLabels($startDate, $endDate, $period),
                'datasets' => $this->formatLeaveDatasets($leaves, $startDate, $endDate, $period),
                'summary' => $this->leaveRepository->getLeaveStats($startDate, $endDate),
            ];
        });
    }

    /**
     * Get department-wise analytics
     */
    public function getDepartmentAnalytics(): array
    {
        $cacheKey = 'department_analytics';
        
        return Cache::remember($cacheKey, 900, function () {
            $departments = $this->departmentRepository->all();
            
            return [
                'labels' => $departments->pluck('name')->toArray(),
                'datasets' => $this->formatDepartmentDatasets($departments),
                'department_details' => $this->getDepartmentDetails($departments),
            ];
        });
    }

    /**
     * Get hiring trends
     */
    public function getHiringTrends(string $period = 'year'): array
    {
        $cacheKey = "hiring_trends_{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            $startDate = $this->getStartDate($period);
            $endDate = Carbon::now();

            $employees = $this->employeeRepository->all()
                ->filter(function ($employee) use ($startDate) {
                    return $employee->hire_date >= $startDate;
                });

            return [
                'labels' => $this->generateMonthLabels($startDate, $endDate),
                'datasets' => $this->formatHiringDatasets($employees, $startDate, $endDate),
                'summary' => $this->getHiringSummary($employees),
            ];
        });
    }

    // Private helper methods

    private function getUserInfo(Employee $employee): array
    {
        return [
            'name' => $employee->full_name,
            'full_name' => $employee->full_name,
            'employee_id' => $employee->employee_id,
            'position' => $employee->position,
            'department' => $employee->department?->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
        ];
    }

    private function getAttendanceSummary(Employee $employee): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        $todayAttendance = $this->attendanceRepository->byDate($today)
            ->where('employee_id', $employee->id)
            ->first();

        $thisMonthAttendances = $this->attendanceRepository->getMonthlyAttendance(
            $thisMonth->year, 
            $thisMonth->month
        )->where('employee_id', $employee->id);

        return [
            'today' => [
                'status' => $todayAttendance?->status ?? 'not_marked',
                'check_in' => $todayAttendance?->check_in?->format('H:i'),
                'check_out' => $todayAttendance?->check_out?->format('H:i'),
                'notes' => $todayAttendance?->notes,
            ],
            'this_month' => [
                'present' => $thisMonthAttendances->where('status', 'present')->count(),
                'absent' => $thisMonthAttendances->where('status', 'absent')->count(),
                'late' => $thisMonthAttendances->where('status', 'late')->count(),
                'total_days' => $thisMonthAttendances->count(),
                'attendance_rate' => $thisMonthAttendances->count() > 0 
                    ? round(($thisMonthAttendances->where('status', 'present')->count() / $thisMonthAttendances->count()) * 100, 2)
                    : 0,
            ]
        ];
    }

    public function getLeaveBalance(Employee $employee): array
    {
        return $this->leaveRepository->getLeaveBalance($employee->id, Carbon::now()->year);
    }

    private function getRecentActivities(Employee $employee): array
    {
        $activities = [];
        
        // Recent attendances
        $recentAttendances = $this->attendanceRepository->byEmployee($employee->id)
            ->take(5);

        foreach ($recentAttendances as $attendance) {
            $activities[] = [
                'type' => 'attendance',
                'description' => "Marked as {$attendance->status}",
                'date' => $attendance->date->format('Y-m-d'),
                'time' => $attendance->check_in ? $attendance->check_in->format('H:i') : null,
            ];
        }

        // Recent leaves
        $recentLeaves = $this->leaveRepository->byEmployee($employee->id)
            ->take(3);

        foreach ($recentLeaves as $leave) {
            $activities[] = [
                'type' => 'leave',
                'description' => "Leave request: {$leave->status}",
                'date' => $leave->start_date->format('Y-m-d'),
                'duration' => "{$leave->days} days",
            ];
        }

        // Sort by date
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, 5);
    }

    private function getTotalEmployees(): int
    {
        return $this->employeeRepository->all()->total();
    }

    private function getActiveInactiveEmployees(): array
    {
        $active = $this->employeeRepository->active()->count();
        $total = $this->getTotalEmployees();
        $inactive = $total - $active;

        return [
            'active' => $active,
            'inactive' => $inactive,
            'total' => $total,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'inactive_percentage' => $total > 0 ? round(($inactive / $total) * 100, 2) : 0,
        ];
    }

    private function getAttendanceSummaryAll(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $todayAttendances = $this->attendanceRepository->getTodayAttendance();
        $thisMonthAttendances = $this->attendanceRepository->getMonthlyAttendance(
            $thisMonth->year, 
            $thisMonth->month
        );

        return [
            'today' => [
                'present' => $todayAttendances->where('status', 'present')->count(),
                'absent' => $todayAttendances->where('status', 'absent')->count(),
                'late' => $todayAttendances->where('status', 'late')->count(),
                'not_marked' => $this->getTotalEmployees() - $todayAttendances->count(),
            ],
            'this_month' => [
                'present' => $thisMonthAttendances->where('status', 'present')->count(),
                'absent' => $thisMonthAttendances->where('status', 'absent')->count(),
                'late' => $thisMonthAttendances->where('status', 'late')->count(),
                'total_records' => $thisMonthAttendances->count(),
            ]
        ];
    }

    private function getLeaveStatistics(): array
    {
        $thisYear = Carbon::now()->year;
        $startDate = Carbon::create($thisYear, 1, 1);
        $endDate = Carbon::create($thisYear, 12, 31);

        return $this->leaveRepository->getLeaveStats($startDate, $endDate);
    }

    private function getDepartmentBreakdown(): array
    {
        $departments = $this->departmentRepository->all();
        $totalEmployees = $this->getTotalEmployees();

        return $departments->map(function ($department) use ($totalEmployees) {
            return [
                'name' => $department->name,
                'employee_count' => $department->employees_count,
                'percentage' => $totalEmployees > 0 
                    ? round(($department->employees_count / $totalEmployees) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }

    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            '6months' => Carbon::now()->subMonths(6),
            'year' => Carbon::now()->startOfYear(),
            '2years' => Carbon::now()->subYears(2),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function generateDateLabels(Carbon $startDate, Carbon $endDate, string $period): array
    {
        $labels = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if ($period === 'week') {
                $labels[] = $current->format('D');
                $current->addDay();
            } elseif ($period === 'month') {
                $labels[] = $current->format('d M');
                $current->addDay();
            } else {
                $labels[] = $current->format('M Y');
                $current->addMonth();
            }
        }

        return $labels;
    }

    private function generateMonthLabels(Carbon $startDate, Carbon $endDate): array
    {
        $labels = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $labels[] = $current->format('M Y');
            $current->addMonth();
        }

        return $labels;
    }

    private function formatAttendanceDatasets($attendances, Carbon $startDate, Carbon $endDate, string $period): array
    {
        $labels = $this->generateDateLabels($startDate, $endDate, $period);
        $presentData = [];
        $absentData = [];
        $lateData = [];

        foreach ($labels as $label) {
            $date = Carbon::parse($label);
            $dayAttendances = $attendances->filter(function ($attendance) use ($date) {
                return $attendance->date->format('Y-m-d') === $date->format('Y-m-d');
            });
            
            $presentData[] = $dayAttendances->where('status', 'present')->count();
            $absentData[] = $dayAttendances->where('status', 'absent')->count();
            $lateData[] = $dayAttendances->where('status', 'late')->count();
        }

        return [
            [
                'label' => 'Present',
                'data' => $presentData,
                'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Absent',
                'data' => $absentData,
                'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Late',
                'data' => $lateData,
                'backgroundColor' => 'rgba(255, 206, 86, 0.8)',
                'borderColor' => 'rgba(255, 206, 86, 1)',
                'borderWidth' => 1
            ]
        ];
    }

    private function formatLeaveDatasets($leaves, Carbon $startDate, Carbon $endDate, string $period): array
    {
        $labels = $this->generateDateLabels($startDate, $endDate, $period);
        $approvedData = [];
        $pendingData = [];
        $rejectedData = [];

        foreach ($labels as $label) {
            $date = Carbon::parse($label);
            $monthLeaves = $leaves->filter(function ($leave) use ($date) {
                return $leave->start_date->format('Y-m') === $date->format('Y-m');
            });
            
            $approvedData[] = $monthLeaves->where('status', 'approved')->count();
            $pendingData[] = $monthLeaves->where('status', 'pending')->count();
            $rejectedData[] = $monthLeaves->where('status', 'rejected')->count();
        }

        return [
            [
                'label' => 'Approved',
                'data' => $approvedData,
                'backgroundColor' => 'rgba(75, 192, 192, 0.8)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Pending',
                'data' => $pendingData,
                'backgroundColor' => 'rgba(255, 206, 86, 0.8)',
                'borderColor' => 'rgba(255, 206, 86, 1)',
                'borderWidth' => 1
            ],
            [
                'label' => 'Rejected',
                'data' => $rejectedData,
                'backgroundColor' => 'rgba(255, 99, 132, 0.8)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1
            ]
        ];
    }

    private function formatDepartmentDatasets($departments): array
    {
        return [
            [
                'label' => 'Employee Count',
                'data' => $departments->pluck('employees_count')->toArray(),
                'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 1
            ]
        ];
    }

    private function getDepartmentDetails($departments): array
    {
        return $departments->map(function ($department) {
            return [
                'name' => $department->name,
                'employee_count' => $department->employees_count,
            ];
        })->toArray();
    }

    private function formatHiringDatasets($employees, Carbon $startDate, Carbon $endDate): array
    {
        $labels = $this->generateMonthLabels($startDate, $endDate);
        $hiringData = [];

        foreach ($labels as $label) {
            $month = Carbon::parse($label);
            $monthHires = $employees->filter(function ($employee) use ($month) {
                return $employee->hire_date->format('Y-m') === $month->format('Y-m');
            });
            
            $hiringData[] = $monthHires->count();
        }

        return [
            [
                'label' => 'New Hires',
                'data' => $hiringData,
                'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.4
            ]
        ];
    }

    private function getHiringSummary($employees): array
    {
        return [
            'total_hired' => $employees->count(),
            'avg_hiring_rate' => round($employees->count() / 12, 2),
            'peak_month' => $this->getPeakHiringMonth($employees),
        ];
    }

    private function getPeakHiringMonth($employees): ?string
    {
        if ($employees->isEmpty()) {
            return null;
        }

        $monthlyHires = $employees->groupBy(function ($employee) {
            return $employee->hire_date->format('Y-m');
        });

        $peakMonth = $monthlyHires->max(function ($group) {
            return $group->count();
        });

        return Carbon::parse($peakMonth->first()->hire_date)->format('F Y');
    }

    
    }
