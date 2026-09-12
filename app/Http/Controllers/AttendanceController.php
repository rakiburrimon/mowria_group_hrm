<?php

namespace App\Http\Controllers;

use App\DataTables\AttendancesDataTable;
use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\DepartmentService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected AttendanceService $attendances,
        protected DepartmentService $departments
    ) {}

    /**
     * Display a listing of attendance records (server-side DataTable).
     */
    public function index(AttendancesDataTable $dataTable)
    {
        $employees = Employee::with('department')->get();
        $departments = $this->departments->active();
        $stats = $this->attendances->statistics();

        return $dataTable->render('attendance.index', compact('employees', 'departments', 'stats'));
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create(): View
    {
        $employees = Employee::with('department')->get();
        $departments = $this->departments->active();

        return view('attendance.create', compact('employees', 'departments'));
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
    public function show(Attendance $attendance): View
    {
        $attendance->load(['employee.department']);

        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record.
     */
    public function edit(Attendance $attendance): View
    {
        $attendance->load(['employee.department']);
        $employees = Employee::with('department')->get();
        $departments = $this->departments->active();

        return view('attendance.edit', compact('attendance', 'employees', 'departments'));
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
     * Display monthly attendance view.
     */
    public function monthlyView(Request $request): View
    {
        $data = $this->attendances->monthlyView(
            $request->get('year', now()->year),
            $request->get('month', now()->month),
            $request->get('department_id'),
            $request->get('employee_id')
        );

        $departments = $this->departments->active();

        return view('attendance.monthly', array_merge($data, [
            'departments' => $departments,
            'departmentId' => $request->get('department_id'),
            'employeeId' => $request->get('employee_id'),
        ]));
    }

    /**
     * Display attendance reports.
     */
    public function reports(Request $request): View
    {
        $data = $this->attendances->reports(
            $request->get('year', now()->year),
            $request->get('month', now()->month),
            $request->get('department_id')
        );

        $departments = $this->departments->active();

        return view('attendance.reports', array_merge($data, [
            'departments' => $departments,
            'year' => $request->get('year', now()->year),
            'month' => $request->get('month', now()->month),
        ]));
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
                'data' => $this->attendances->statistics()
            ]);
        }, 'Failed to fetch attendance statistics');
    }
}
