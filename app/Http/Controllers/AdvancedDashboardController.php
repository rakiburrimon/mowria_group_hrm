<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvancedDashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display advanced dashboard for admin/HR users
     */
    public function index(Request $request): View
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return view('errors.unauthorized');
            }

            // Get advanced dashboard data
            $dashboardData = $this->dashboardService->getAdvancedDashboard();

            // Add analytics data
            $dashboardData['analytics'] = $this->getAnalyticsData($request);

            // Add filtering options
            $dashboardData['filters'] = $this->getFilterOptions();

            return view('dashboard.admin', compact('dashboardData'));

        } catch (\Exception $e) {
            return view('errors.dashboard-error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get analytics data for charts
     */
    private function getAnalyticsData(Request $request): array
    {
        $period = $request->get('period', 'month'); // Default to month
        $startDate = $this->getStartDate($period);
        $endDate = now();

        return [
            'attendance_analytics' => $this->dashboardService->getAttendanceAnalytics($period),
            'leave_analytics' => $this->dashboardService->getLeaveAnalytics($period),
            'department_analytics' => $this->dashboardService->getDepartmentAnalytics(),
            'hiring_trends' => $this->dashboardService->getHiringTrends($period),
            'period' => $period,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ]
        ];
    }

    /**
     * Get filter options for dashboard
     */
    private function getFilterOptions(): array
    {
        return [
            'periods' => [
                ['value' => 'week', 'label' => 'Last 7 Days'],
                ['value' => 'month', 'label' => 'Last 30 Days'],
                ['value' => '6months', 'label' => 'Last 6 Months'],
                ['value' => 'year', 'label' => 'Last Year'],
                ['value' => '2years', 'label' => 'Last 2 Years'],
            ],
            'departments' => $this->getDepartmentOptions(),
            'years' => $this->getYearOptions(),
        ];
    }

    /**
     * Get department options for filtering
     */
    private function getDepartmentOptions(): array
    {
        $departments = \App\Models\Department::active()->get();
        
        return $departments->map(function ($department) {
            return [
                'id' => $department->id,
                'name' => $department->name,
                'employee_count' => $department->employees_count ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get year options for filtering
     */
    private function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = [];
        
        for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
            $years[] = [
                'value' => $year,
                'label' => (string)$year,
            ];
        }
        
        return $years;
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            '6months' => now()->subMonths(6),
            'year' => now()->subYear(),
            '2years' => now()->subYears(2),
            default => now()->subDays(30),
        };
    }
}
