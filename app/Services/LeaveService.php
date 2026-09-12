<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Traits\LogsActions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service layer for leave request operations.
 *
 * Handles validation, balance checks, approval workflows,
 * notifications and activity logging.
 */
class LeaveService
{
    use LogsActions;

    public function __construct(
        protected LeaveRepositoryInterface $leaves
    ) {}

    /**
     * Paginate leave requests for a specific employee.
     */
    public function paginateForEmployee(Employee $employee, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Leave::with(['approvals.approver', 'employee'])
            ->where('employee_id', $employee->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Paginate pending leave requests for the approval panel.
     */
    public function paginatePending(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Leave::with(['employee', 'approvals.approver', 'leaveType'])
            ->where('status', Leave::STATUS_PENDING);

        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        return $query->orderBy('start_date', 'asc')->paginate($perPage);
    }

    /**
     * Create a new leave request for an employee.
     */
    public function create(Employee $employee, array $data): Leave
    {
        return DB::transaction(function () use ($employee, $data) {
            $data['employee_id'] = $employee->id;

            $leaveBalance = LeaveBalance::forEmployee($employee->id)
                ->forYear(now()->year)
                ->byType($data['type'])
                ->first();

            if (!$leaveBalance || !$leaveBalance->checkBalance($data['days'])) {
                throw new \InvalidArgumentException('Insufficient leave balance');
            }

            $leave = $this->leaves->create($data);

            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_APPLIED);

            $leaveType = LeaveType::where('code', $data['type'])->first();
            if ($leaveType && $leaveType->requires_approval) {
                $this->createApprovalWorkflow($leave);
            }

            $this->logAction('leave request created', $leave);

            return $leave;
        });
    }

    /**
     * Update an existing leave request.
     */
    public function update(Leave $leave, array $data): bool
    {
        return DB::transaction(function () use ($leave, $data) {
            if (isset($data['days']) && $data['days'] != $leave->days) {
                $leaveBalance = LeaveBalance::forEmployee($leave->employee_id)
                    ->forYear(now()->year)
                    ->byType($data['type'])
                    ->first();

                if (!$leaveBalance || !$leaveBalance->checkBalance($data['days'])) {
                    throw new \InvalidArgumentException('Insufficient leave balance');
                }
            }

            $updated = $leave->update($data);

            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_UPDATED);
            $this->logAction('leave request updated', $leave);

            return $updated;
        });
    }

    /**
     * Cancel a pending leave request.
     */
    public function cancel(Leave $leave): bool
    {
        return DB::transaction(function () use ($leave) {
            $updated = $leave->update(['status' => Leave::STATUS_CANCELLED]);

            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_CANCELLED);
            $this->logAction('leave request cancelled', $leave);

            return $updated;
        });
    }

    /**
     * Approve or reject a leave request.
     */
    public function approve(Leave $leave, array $data, User $user): LeaveApproval
    {
        return DB::transaction(function () use ($leave, $data, $user) {
            $approval = LeaveApproval::updateOrCreate(
                [
                    'leave_id' => $leave->id,
                    'approver_id' => $user->id,
                    'level' => $data['level'],
                ],
                [
                    'status' => $data['status'],
                    'comments' => $data['comments'] ?? null,
                    'approved_at' => $data['status'] === LeaveApproval::STATUS_APPROVED ? now() : null,
                ]
            );

            $this->updateLeaveStatus($leave);

            if ($data['status'] === LeaveApproval::STATUS_APPROVED) {
                $this->updateLeaveBalance($leave);
            }

            $notificationType = $data['status'] === LeaveApproval::STATUS_APPROVED
                ? Notification::TYPE_LEAVE_APPROVED
                : Notification::TYPE_LEAVE_REJECTED;
            $this->createLeaveNotification($leave, $notificationType);

            $this->logAction('leave request ' . $data['status'], $leave, [
                'level' => $data['level'],
                'approver_id' => $user->id,
            ]);

            return $approval;
        });
    }

    /**
     * Get leave balance for an employee.
     */
    public function getLeaveBalance(Employee $employee)
    {
        return LeaveBalance::forEmployee($employee->id)
            ->forYear(now()->year)
            ->with('leaveType')
            ->get();
    }

    /**
     * Create a notification for a leave action.
     */
    protected function createLeaveNotification(Leave $leave, string $type): void
    {
        Notification::create([
            'user_id' => $leave->employee->user_id,
            'title' => $this->getNotificationTitle($type),
            'message' => $this->getNotificationMessage($leave, $type),
            'type' => $type,
            'related_id' => $leave->id,
            'related_type' => Leave::class,
        ]);

        if ($type === Notification::TYPE_LEAVE_APPLIED) {
            $this->notifyApprovers($leave, $type);
        }
    }

    /**
     * Notify managers/HR about a new leave request.
     */
    protected function notifyApprovers(Leave $leave, string $type): void
    {
        $managers = User::where('role', 'manager')->get();

        foreach ($managers as $manager) {
            Notification::create([
                'user_id' => $manager->id,
                'title' => 'New Leave Request',
                'message' => "{$leave->employee->full_name} has applied for leave",
                'type' => $type,
                'related_id' => $leave->id,
                'related_type' => Leave::class,
            ]);
        }
    }

    /**
     * Create approval workflow for multi-level approval.
     */
    protected function createApprovalWorkflow(Leave $leave): void
    {
        $levels = [LeaveApproval::LEVEL_MANAGER, LeaveApproval::LEVEL_HR];

        foreach ($levels as $level) {
            LeaveApproval::create([
                'leave_id' => $leave->id,
                'level' => $level,
                'status' => LeaveApproval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Update leave status based on approvals.
     */
    protected function updateLeaveStatus(Leave $leave): void
    {
        $approvals = $leave->approvals;
        $requiredLevels = [LeaveApproval::LEVEL_MANAGER, LeaveApproval::LEVEL_HR];

        $allApproved = true;
        foreach ($requiredLevels as $level) {
            $approval = $approvals->where('level', $level)->first();
            if (!$approval || $approval->status !== LeaveApproval::STATUS_APPROVED) {
                $allApproved = false;
                break;
            }
        }

        $anyRejected = $approvals->where('status', LeaveApproval::STATUS_REJECTED)->count() > 0;

        if ($anyRejected) {
            $leave->update(['status' => Leave::STATUS_REJECTED]);
        } elseif ($allApproved) {
            $leave->update(['status' => Leave::STATUS_APPROVED]);
        }
    }

    /**
     * Update leave balance when leave is approved.
     */
    protected function updateLeaveBalance(Leave $leave): void
    {
        $balance = LeaveBalance::forEmployee($leave->employee_id)
            ->forYear($leave->start_date->year)
            ->byType($leave->type)
            ->first();

        if ($balance) {
            $balance->updateBalance($leave->days);
        }
    }

    /**
     * Get notification title.
     */
    protected function getNotificationTitle(string $type): string
    {
        return match($type) {
            Notification::TYPE_LEAVE_APPLIED => 'Leave Request Submitted',
            Notification::TYPE_LEAVE_APPROVED => 'Leave Request Approved',
            Notification::TYPE_LEAVE_REJECTED => 'Leave Request Rejected',
            Notification::TYPE_LEAVE_CANCELLED => 'Leave Request Cancelled',
            Notification::TYPE_LEAVE_UPDATED => 'Leave Request Updated',
            default => 'Leave Notification',
        };
    }

    /**
     * Get notification message.
     */
    protected function getNotificationMessage(Leave $leave, string $type): string
    {
        return match($type) {
            Notification::TYPE_LEAVE_APPLIED => "Your leave request for {$leave->days} days has been submitted.",
            Notification::TYPE_LEAVE_APPROVED => "Your leave request for {$leave->days} days has been approved.",
            Notification::TYPE_LEAVE_REJECTED => "Your leave request for {$leave->days} days has been rejected.",
            Notification::TYPE_LEAVE_CANCELLED => "Your leave request has been cancelled.",
            Notification::TYPE_LEAVE_UPDATED => "Your leave request has been updated.",
            default => 'Leave notification',
        };
    }
}
