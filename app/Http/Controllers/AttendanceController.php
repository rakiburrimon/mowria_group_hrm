<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\StoreAttendanceRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records.
     */
    public function index(Request $request): View
    {
        $query = Attendance::with(['employee.department'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in', 'desc');

        // Filters
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->get('employee_id'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->get('department_id'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->get('date_to'));
        }

        $attendances = $query->paginate(20);

        // Get filter options
        $employees = Employee::with('department')->get();
        $departments = Department::active()->get();

        return view('attendance.index', compact('attendances', 'employees', 'departments'));
    }

    /**
     * Show the form for creating a new attendance record.
     */
    public function create(): View
    {
        $employees = Employee::with('department')->get();
        $departments = Department::active()->get();

        return view('attendance.create', compact('employees', 'departments'));
    }

    /**
     * Store a newly created attendance record in storage.
     */
    public function store(StoreAttendanceRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Check if attendance already exists for this employee and date
            $existingAttendance = Attendance::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record already exists for this employee and date',
                    'errors' => ['date' => 'Attendance record already exists for this date']
                ], 422);
            }

            $attendance = Attendance::create($data);

            // Calculate work hours, late minutes, and early leave
            if ($attendance->check_in && $attendance->check_out) {
                $attendance->calculateWorkHours();
                $attendance->calculateLateMinutes();
                $attendance->calculateEarlyLeaveMinutes();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record created successfully',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create attendance record: ' . $e->getMessage()
            ], 500);
        }
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
        $departments = Department::active()->get();

        return view('attendance.edit', compact('attendance', 'employees', 'departments'));
    }

    /**
     * Update the specified attendance record in storage.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Check if another attendance record exists for this employee and date
            $existingAttendance = Attendance::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->where('id', '!=', $attendance->id)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record already exists for this employee and date',
                    'errors' => ['date' => 'Attendance record already exists for this date']
                ], 422);
            }

            $attendance->update($data);

            // Recalculate work hours, late minutes, and early leave
            if ($attendance->check_in && $attendance->check_out) {
                $attendance->calculateWorkHours();
                $attendance->calculateLateMinutes();
                $attendance->calculateEarlyLeaveMinutes();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record updated successfully',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified attendance record from storage.
     */
    public function destroy(Attendance $attendance): JsonResponse
    {
        try {
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display monthly attendance view.
     */
    public function monthlyView(Request $request): View
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $departmentId = $request->get('department_id');
        $employeeId = $request->get('employee_id');

        $query = Employee::with(['department', 'attendances' => function ($q) use ($year, $month) {
            $q->whereYear('date', $year)->whereMonth('date', $month);
        }]);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->get();

        // Get month days
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $monthDays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $monthDays[] = Carbon::create($year, $month, $day);
        }

        // Get departments for filter
        $departments = Department::active()->get();

        return view('attendance.monthly', compact(
            'employees', 
            'monthDays', 
            'year', 
            'month', 
            'departments',
            'departmentId',
            'employeeId'
        ));
    }

    /**
     * Display attendance reports.
     */
    public function reports(Request $request): View
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $departmentId = $request->get('department_id');

        $query = Attendance::with(['employee.department'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $attendances = $query->get();

        // Calculate statistics
        $statistics = [
            'total_employees' => $attendances->pluck('employee_id')->unique()->count(),
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', Attendance::STATUS_PRESENT)->count(),
            'late_days' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'absent_days' => $attendances->where('status', Attendance::STATUS_ABSENT)->count(),
            'leave_days' => $attendances->where('status', Attendance::STATUS_LEAVE)->count(),
            'total_work_hours' => $attendances->sum('work_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_early_leave_minutes' => $attendances->sum('early_leave_minutes'),
        ];

        // Department-wise statistics
        $departmentStats = [];
        if ($departmentId) {
            $department = Department::find($departmentId);
            $departmentStats[$department->name] = $statistics;
        } else {
            $departments = Department::active()->get();
            foreach ($departments as $department) {
                $deptAttendances = $attendances->where('employee.department_id', $department->id);
                $departmentStats[$department->name] = [
                    'total_employees' => $deptAttendances->pluck('employee_id')->unique()->count(),
                    'total_days' => $deptAttendances->count(),
                    'present_days' => $deptAttendances->where('status', Attendance::STATUS_PRESENT)->count(),
                    'late_days' => $deptAttendances->where('status', Attendance::STATUS_LATE)->count(),
                    'absent_days' => $deptAttendances->where('status', Attendance::STATUS_ABSENT)->count(),
                    'leave_days' => $deptAttendances->where('status', Attendance::STATUS_LEAVE)->count(),
                    'total_work_hours' => $deptAttendances->sum('work_hours'),
                    'total_overtime_hours' => $deptAttendances->sum('overtime_hours'),
                    'total_late_minutes' => $deptAttendances->sum('late_minutes'),
                    'total_early_leave_minutes' => $deptAttendances->sum('early_leave_minutes'),
                ];
            }
        }

        $departments = Department::active()->get();

        return view('attendance.reports', compact(
            'attendances',
            'statistics',
            'departmentStats',
            'year',
            'month',
            'departments'
        ));
    }

    /**
     * Handle check-in action.
     */
    public function checkIn(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;
            $today = now()->format('Y-m-d');

            // Check if already checked in today
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if ($existingAttendance && $existingAttendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked in today'
                ], 422);
            }

            // Create or update attendance record
            $attendance = $existingAttendance ?? new Attendance();
            $attendance->employee_id = $employee->id;
            $attendance->date = $today;
            $attendance->check_in = now()->format('H:i');
            $attendance->status = Attendance::STATUS_PRESENT;
            $attendance->save();

            // Calculate late minutes
            $attendance->calculateLateMinutes();

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle check-out action.
     */
    public function checkOut(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $employee = $user->employee;
            $today = now()->format('Y-m-d');

            // Find today's attendance record
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if (!$attendance || !$attendance->check_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'No check-in record found for today'
                ], 422);
            }

            if ($attendance->check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Already checked out today'
                ], 422);
            }

            // Update check-out time
            $attendance->check_out = now()->format('H:i');
            $attendance->save();

            // Calculate work hours and early leave
            $attendance->calculateWorkHours();
            $attendance->calculateEarlyLeaveMinutes();

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully',
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance statistics for dashboard.
     */
    public function statistics(): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        $statistics = [
            'today_present' => Attendance::where('date', $today)
                ->where('status', Attendance::STATUS_PRESENT)->count(),
            'today_absent' => Attendance::where('date', $today)
                ->where('status', Attendance::STATUS_ABSENT)->count(),
            'today_late' => Attendance::where('date', $today)
                ->where('status', Attendance::STATUS_LATE)->count(),
            'month_present' => Attendance::where('date', 'like', $thisMonth . '%')
                ->where('status', Attendance::STATUS_PRESENT)->count(),
            'month_absent' => Attendance::where('date', 'like', $thisMonth . '%')
                ->where('status', Attendance::STATUS_ABSENT)->count(),
            'month_late' => Attendance::where('date', 'like', $thisMonth . '%')
                ->where('status', Attendance::STATUS_LATE)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
