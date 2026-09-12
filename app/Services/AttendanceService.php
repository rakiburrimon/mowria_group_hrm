<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Department;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Traits\LogsActions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service layer for attendance operations.
 *
 * Handles CRUD, check-in/check-out, monthly views and reports.
 * Activity is logged for each write action.
 */
class AttendanceService
{
    use LogsActions;

    public function __construct(
        protected AttendanceRepositoryInterface $attendances
    ) {}

    /**
     * Paginate attendance records with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Attendance::with(['employee.department'])
            ->orderBy('date', 'desc')
            ->orderBy('check_in', 'desc');

        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new attendance record.
     */
    public function create(array $data): Attendance
    {
        return DB::transaction(function () use ($data) {
            $existing = Attendance::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('Attendance record already exists for this employee and date');
            }

            $attendance = $this->attendances->create($data);

            if ($attendance->check_in && $attendance->check_out) {
                $attendance->calculateWorkHours();
                $attendance->calculateLateMinutes();
                $attendance->calculateEarlyLeaveMinutes();
            }

            $this->logAction('attendance created', $attendance);

            return $attendance;
        });
    }

    /**
     * Update an existing attendance record.
     */
    public function update(Attendance $attendance, array $data): bool
    {
        return DB::transaction(function () use ($attendance, $data) {
            $existing = Attendance::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->where('id', '!=', $attendance->id)
                ->first();

            if ($existing) {
                throw new \InvalidArgumentException('Attendance record already exists for this employee and date');
            }

            $updated = $attendance->update($data);

            if ($attendance->check_in && $attendance->check_out) {
                $attendance->calculateWorkHours();
                $attendance->calculateLateMinutes();
                $attendance->calculateEarlyLeaveMinutes();
            }

            $this->logAction('attendance updated', $attendance);

            return $updated;
        });
    }

    /**
     * Delete an attendance record.
     */
    public function delete(Attendance $attendance): bool
    {
        $deleted = $attendance->delete();

        $this->logAction('attendance deleted', $attendance);

        return $deleted;
    }

    /**
     * Handle employee check-in for today.
     */
    public function checkIn(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee) {
            $today = now()->format('Y-m-d');

            $existing = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if ($existing && $existing->check_in) {
                throw new \InvalidArgumentException('Already checked in today');
            }

            $attendance = $existing ?? new Attendance();
            $attendance->employee_id = $employee->id;
            $attendance->date = $today;
            $attendance->check_in = now()->format('H:i');
            $attendance->status = Attendance::STATUS_PRESENT;
            $attendance->save();

            $attendance->calculateLateMinutes();

            $this->logAction('employee checked in', $attendance);

            return $attendance;
        });
    }

    /**
     * Handle employee check-out for today.
     */
    public function checkOut(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee) {
            $today = now()->format('Y-m-d');

            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $today)
                ->first();

            if (!$attendance || !$attendance->check_in) {
                throw new \InvalidArgumentException('No check-in record found for today');
            }

            if ($attendance->check_out) {
                throw new \InvalidArgumentException('Already checked out today');
            }

            $attendance->check_out = now()->format('H:i');
            $attendance->save();

            $attendance->calculateWorkHours();
            $attendance->calculateEarlyLeaveMinutes();

            $this->logAction('employee checked out', $attendance);

            return $attendance;
        });
    }

    /**
     * Get monthly attendance view data.
     */
    public function monthlyView(int $year, int $month, ?int $departmentId = null, ?int $employeeId = null): array
    {
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

        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $monthDays = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $monthDays[] = Carbon::create($year, $month, $day);
        }

        return [
            'employees' => $employees,
            'monthDays' => $monthDays,
            'year' => $year,
            'month' => $month,
        ];
    }

    /**
     * Get attendance reports for a given month.
     */
    public function reports(int $year, int $month, ?int $departmentId = null): array
    {
        $query = Attendance::with(['employee.department'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $attendances = $query->get();

        $statistics = $this->buildStatistics($attendances);

        $departmentStats = [];
        if ($departmentId) {
            $department = Department::find($departmentId);
            $departmentStats[$department->name] = $statistics;
        } else {
            foreach (Department::active()->get() as $department) {
                $deptAttendances = $attendances->where('employee.department_id', $department->id);
                $departmentStats[$department->name] = $this->buildStatistics($deptAttendances);
            }
        }

        return [
            'attendances' => $attendances,
            'statistics' => $statistics,
            'departmentStats' => $departmentStats,
        ];
    }

    /**
     * Get attendance statistics for the dashboard.
     */
    public function statistics(): array
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        return [
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
    }

    /**
     * Build statistics for a collection of attendance records.
     */
    protected function buildStatistics($attendances): array
    {
        return [
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
    }
}
