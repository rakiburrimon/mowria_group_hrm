<?php

namespace App\Repositories\Eloquent;

use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    protected Attendance $model;

    public function __construct(Attendance $model)
    {
        $this->model = $model;
    }

    public function all(): LengthAwarePaginator
    {
        return $this->model->with('employee')
            ->orderBy('date', 'desc')
            ->paginate(20);
    }

    public function find(int $id): ?Attendance
    {
        return $this->model->with('employee')->find($id);
    }

    public function create(array $data): Attendance
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $attendance = $this->find($id);
        return $attendance ? $attendance->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $attendance = $this->find($id);
        return $attendance ? $attendance->delete() : false;
    }

    public function byDate(Carbon $date): Collection
    {
        return $this->model->byDate($date)
            ->with('employee')
            ->get();
    }

    public function byEmployee(int $employeeId): LengthAwarePaginator
    {
        return $this->model->where('employee_id', $employeeId)
            ->orderBy('date', 'desc')
            ->paginate(20);
    }

    public function byDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model->byDateRange($startDate, $endDate)
            ->with('employee')
            ->get();
    }

    public function getAttendanceStats(Carbon $startDate, Carbon $endDate): array
    {
        $attendances = $this->byDateRange($startDate, $endDate);
        
        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', Attendance::STATUS_PRESENT)->count(),
            'absent' => $attendances->where('status', Attendance::STATUS_ABSENT)->count(),
            'late' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'leave' => $attendances->where('status', Attendance::STATUS_LEAVE)->count(),
            'holiday' => $attendances->where('status', Attendance::STATUS_HOLIDAY)->count(),
        ];
    }

    public function getTodayAttendance(): Collection
    {
        return $this->byDate(Carbon::today());
    }

    public function getMonthlyAttendance(int $year, int $month): Collection
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        
        return $this->byDateRange($startDate, $endDate);
    }
}
