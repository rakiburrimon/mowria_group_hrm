<?php

namespace App\Repositories\Eloquent;

use App\Models\Leave;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class LeaveRepository implements LeaveRepositoryInterface
{
    protected Leave $model;

    public function __construct(Leave $model)
    {
        $this->model = $model;
    }

    public function all(): LengthAwarePaginator
    {
        return $this->model->with(['employee', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function find(int $id): ?Leave
    {
        return $this->model->with(['employee', 'approvedBy'])->find($id);
    }

    public function create(array $data): Leave
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $leave = $this->find($id);
        return $leave ? $leave->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $leave = $this->find($id);
        return $leave ? $leave->delete() : false;
    }

    public function pending(): Collection
    {
        return $this->model->pending()
            ->with(['employee', 'approvedBy'])
            ->orderBy('created_at')
            ->get();
    }

    public function byEmployee(int $employeeId): LengthAwarePaginator
    {
        return $this->model->where('employee_id', $employeeId)
            ->with(['employee', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function byDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model->byDateRange($startDate, $endDate)
            ->with(['employee', 'approvedBy'])
            ->get();
    }

    public function getLeaveStats(Carbon $startDate, Carbon $endDate): array
    {
        $leaves = $this->byDateRange($startDate, $endDate);
        
        return [
            'total_requests' => $leaves->count(),
            'approved' => $leaves->where('status', Leave::STATUS_APPROVED)->count(),
            'pending' => $leaves->where('status', Leave::STATUS_PENDING)->count(),
            'rejected' => $leaves->where('status', Leave::STATUS_REJECTED)->count(),
            'cancelled' => $leaves->where('status', Leave::STATUS_CANCELLED)->count(),
            'total_days' => $leaves->where('status', Leave::STATUS_APPROVED)->sum('days'),
            'by_type' => $leaves->where('status', Leave::STATUS_APPROVED)->groupBy('type')->map->count()->toArray(),
        ];
    }

    public function getLeaveBalance(int $employeeId, int $year): array
    {
        $totalLeaveDays = 21; // Standard annual leave
        $usedLeaveDays = $this->model->where('employee_id', $employeeId)
            ->where('status', Leave::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->sum('days');

        $pendingLeaveDays = $this->model->where('employee_id', $employeeId)
            ->where('status', Leave::STATUS_PENDING)
            ->whereYear('start_date', $year)
            ->sum('days');

        return [
            'total' => $totalLeaveDays,
            'used' => $usedLeaveDays,
            'pending' => $pendingLeaveDays,
            'remaining' => $totalLeaveDays - $usedLeaveDays - $pendingLeaveDays,
            'usage_percentage' => round(($usedLeaveDays / $totalLeaveDays) * 100, 2),
        ];
    }

    public function approve(int $id, int $approvedBy): bool
    {
        return $this->update($id, [
            'status' => Leave::STATUS_APPROVED,
            'approved_by' => $approvedBy,
        ]);
    }

    public function reject(int $id, int $approvedBy, string $remarks): bool
    {
        return $this->update($id, [
            'status' => Leave::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'remarks' => $remarks,
        ]);
    }
}
