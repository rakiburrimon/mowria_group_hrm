<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrivateDashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get private dashboard data for logged-in user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Get employee associated with the user
            $employee = $user->employee;
            
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee profile not found. Please contact HR.',
                ], 404);
            }

            // Get private dashboard data
            $dashboardData = $this->dashboardService->getPrivateDashboard($employee);

            // Add notifications
            $dashboardData['notifications'] = $this->getUserNotifications($employee);

            // Add chart-ready data
            $dashboardData['charts'] = $this->getChartData($employee);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user notifications
     */
    private function getUserNotifications(Employee $employee): array
    {
        $notifications = [];

        // Pending leave requests
        $pendingLeaves = $employee->leaves()->pending()->count();
        if ($pendingLeaves > 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Pending Leave Requests',
                'message' => "You have {$pendingLeaves} pending leave request(s).",
                'icon' => 'calendar',
                'action_url' => '/leaves',
            ];
        }

        // Today's attendance not marked
        $todayAttendance = $employee->attendances()->byDate(now())->first();
        if (!$todayAttendance) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Mark Attendance',
                'message' => 'Please mark your attendance for today.',
                'icon' => 'clock',
                'action_url' => '/attendance/mark',
            ];
        }

        // Leave balance warning
        $leaveBalance = $this->dashboardService->getLeaveBalance($employee);
        if ($leaveBalance['remaining'] <= 2) {
            $notifications[] = [
                'type' => 'error',
                'title' => 'Low Leave Balance',
                'message' => "You have only {$leaveBalance['remaining']} days of leave remaining.",
                'icon' => 'alert-circle',
                'action_url' => '/leaves/balance',
            ];
        }

        return $notifications;
    }

    /**
     * Get chart-ready data for user dashboard
     */
    private function getChartData(Employee $employee): array
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        // Attendance chart data
        $attendanceData = $employee->attendances()
            ->byDateRange($startDate, $endDate)
            ->orderBy('date')
            ->get()
            ->groupBy(function ($attendance) {
                return $attendance->date->format('Y-m-d');
            });

        $labels = [];
        $presentData = [];
        $absentData = [];
        $lateData = [];

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dayAttendances = $attendanceData->get($dateKey, collect());

            $labels[] = $date->format('M d');
            $presentData[] = $dayAttendances->where('status', 'present')->count();
            $absentData[] = $dayAttendances->where('status', 'absent')->count();
            $lateData[] = $dayAttendances->where('status', 'late')->count();
        }

        // Leave balance chart data
        $leaveTypes = ['annual', 'sick', 'personal'];
        $leaveData = [];
        $leaveLabels = [];

        foreach ($leaveTypes as $type) {
            $used = $employee->leaves()
                ->where('type', $type)
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->sum('days');

            $leaveData[] = $used;
            $leaveLabels[] = ucfirst($type);
        }

        return [
            'attendance' => [
                'labels' => $labels,
                'datasets' => [
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
                ]
            ],
            'leave_balance' => [
                'labels' => $leaveLabels,
                'datasets' => [
                    [
                        'label' => 'Used Leave Days',
                        'data' => $leaveData,
                        'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ]
        ];
    }
}
