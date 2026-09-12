<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected AttendanceService $attendances
    ) {}

    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $attendances = $this->attendances->paginate($request->all());

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'message' => 'Attendance records retrieved successfully'
            ]);
        }, 'Failed to fetch attendance records');
    }

    /**
     * Store a newly created attendance record in storage.
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $attendance = $this->attendances->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Attendance record created successfully',
                'data' => $attendance
            ]);
        }, 'Failed to create attendance record');
    }

    /**
     * Display the specified attendance record.
     */
    public function show(Attendance $attendance): JsonResponse
    {
        return $this->handleService(function () use ($attendance) {
            $attendance->load(['employee.department']);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Attendance record retrieved successfully'
            ]);
        }, 'Failed to fetch attendance record');
    }

    /**
     * Update the specified attendance record in storage.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        return $this->handleService(function () use ($request, $attendance) {
            $this->attendances->update($attendance, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Attendance record updated successfully',
                'data' => $attendance
            ]);
        }, 'Failed to update attendance record');
    }

    /**
     * Remove the specified attendance record from storage.
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        return $this->handleService(function () use ($attendance) {
            $this->attendances->delete($attendance);

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ]);
        }, 'Failed to delete attendance record');
    }

    /**
     * Get monthly attendance view.
     */
    public function monthlyView(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $data = $this->attendances->monthlyView(
                $request->get('year', now()->year),
                $request->get('month', now()->month),
                $request->get('department_id'),
                $request->get('employee_id')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Monthly attendance data retrieved successfully'
            ]);
        }, 'Failed to fetch monthly attendance data');
    }

    /**
     * Get attendance reports.
     */
    public function reports(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $data = $this->attendances->reports(
                $request->get('year', now()->year),
                $request->get('month', now()->month),
                $request->get('department_id')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Attendance reports retrieved successfully'
            ]);
        }, 'Failed to fetch attendance reports');
    }

    /**
     * Handle check-in action.
     */
    public function checkIn(Request $request): JsonResponse
    {
        return $this->handleService(function () {
            $attendance = $this->attendances->checkIn(Auth::user()->employee);

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully',
                'data' => $attendance
            ]);
        }, 'Failed to check in');
    }

    /**
     * Handle check-out action.
     */
    public function checkOut(Request $request): JsonResponse
    {
        return $this->handleService(function () {
            $attendance = $this->attendances->checkOut(Auth::user()->employee);

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully',
                'data' => $attendance
            ]);
        }, 'Failed to check out');
    }

    /**
     * Get attendance statistics for dashboard.
     */
    public function statistics(): JsonResponse
    {
        return $this->handleService(function () {
            return response()->json([
                'success' => true,
                'data' => $this->attendances->statistics(),
                'message' => 'Attendance statistics retrieved successfully'
            ]);
        }, 'Failed to fetch attendance statistics');
    }

    /**
     * Get employee attendance summary.
     */
    public function employeeSummary(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $employeeId = $request->get('employee_id');
            $year = $request->get('year', now()->year);
            $month = $request->get('month', now()->month);

            if (!$employeeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee ID is required'
                ], 422);
            }

            $summary = Attendance::getMonthlySummary($employeeId, $year, $month);

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Employee attendance summary retrieved successfully'
            ]);
        }, 'Failed to fetch employee attendance summary');
    }
}
