<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdvancedDashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Advanced Dashboard API Working!',
            'data' => [
                'total_employees' => 50,
                'active_employees' => 45,
                'inactive_employees' => 5,
                'attendance_today' => [
                    'present' => 40,
                    'absent' => 5,
                    'late' => 3
                ]
            ]
        ]);
    }
}
