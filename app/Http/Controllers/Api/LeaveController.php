<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveApproval;
use App\Models\Notification;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display a listing of leaves for the authenticated employee.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        $query = Leave::with(['approvals.approver', 'employee'])
            ->where('employee_id', $employee->id);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('end_date', '<=', $request->get('date_to'));
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leaves,
            'message' => 'Leave requests retrieved successfully'
        ]);
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $employee = $user->employee;

            $data = $request->validated();
            $data['employee_id'] = $employee->id;

            // Check leave balance
            $leaveBalance = LeaveBalance::forEmployee($employee->id)
                ->forYear(now()->year)
                ->byType($data['type'])
                ->first();

            if (!$leaveBalance || !$leaveBalance->checkBalance($data['days'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient leave balance',
                    'errors' => ['days' => 'You do not have enough leave days for this request.']
                ], 422);
            }

            $leave = Leave::create($data);

            // Create notification for managers/HR
            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_APPLIED);

            // Create approval workflow if multi-level approval is required
            $leaveType = LeaveType::where('code', $data['type'])->first();
            if ($leaveType && $leaveType->requires_approval) {
                $this->createApprovalWorkflow($leave);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $leave
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified leave.
     */
    public function show(Leave $leave): JsonResponse
    {
        $leave->load(['employee', 'approvals.approver', 'approvedBy']);
        
        // Check if user can view this leave
        $user = Auth::user();
        if (!$this->canViewLeave($leave, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $leave,
            'message' => 'Leave request retrieved successfully'
        ]);
    }

    /**
     * Update the specified leave in storage.
     */
    public function update(UpdateLeaveRequest $request, Leave $leave): JsonResponse
    {
        try {
            // Only allow updating pending leaves
            if ($leave->status !== Leave::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update processed leave requests'
                ], 403);
            }

            // Check if user can edit this leave
            $user = Auth::user();
            if (!$this->canEditLeave($leave, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            DB::beginTransaction();

            $data = $request->validated();
            
            // Check leave balance if days changed
            if (isset($data['days']) && $data['days'] != $leave->days) {
                $leaveBalance = LeaveBalance::forEmployee($leave->employee_id)
                    ->forYear(now()->year)
                    ->byType($data['type'])
                    ->first();

                if (!$leaveBalance || !$leaveBalance->checkBalance($data['days'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient leave balance',
                        'errors' => ['days' => 'You do not have enough leave days for this request.']
                    ], 422);
                }
            }

            $leave->update($data);

            // Create notification
            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_UPDATED);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request updated successfully',
                'data' => $leave
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified leave from storage.
     */
    public function destroy(Leave $leave): JsonResponse
    {
        try {
            // Only allow cancelling pending leaves
            if ($leave->status !== Leave::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel processed leave requests'
                ], 403);
            }

            // Check if user can cancel this leave
            $user = Auth::user();
            if (!$this->canEditLeave($leave, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            DB::beginTransaction();

            $leave->update(['status' => Leave::STATUS_CANCELLED]);

            // Create notification
            $this->createLeaveNotification($leave, Notification::TYPE_LEAVE_CANCELLED);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending leave requests for approval panel.
     */
    public function approvalPanel(Request $request): JsonResponse
    {
        $query = Leave::with(['employee', 'approvals.approver', 'leaveType'])
            ->where('status', Leave::STATUS_PENDING);

        // Filters
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->get('department_id'));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->get('date_from'));
        }

        $leaves = $query->orderBy('start_date', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $leaves,
            'message' => 'Pending leave requests retrieved successfully'
        ]);
    }

    /**
     * Approve or reject leave request.
     */
    public function approve(ApproveLeaveRequest $request, Leave $leave): JsonResponse
    {
        try {
            $user = Auth::user();
            $data = $request->validated();

            DB::beginTransaction();

            // Create or update approval
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

            // Check if all required approvals are completed
            $this->updateLeaveStatus($leave);

            // Update leave balance if approved
            if ($data['status'] === LeaveApproval::STATUS_APPROVED) {
                $this->updateLeaveBalance($leave);
            }

            // Create notification for employee
            $notificationType = $data['status'] === LeaveApproval::STATUS_APPROVED 
                ? Notification::TYPE_LEAVE_APPROVED 
                : Notification::TYPE_LEAVE_REJECTED;
            $this->createLeaveNotification($leave, $notificationType);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Leave request ' . $data['status'] . ' successfully',
                'data' => $approval
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process leave request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave balance for employee.
     */
    public function leaveBalance(): JsonResponse
    {
        $user = Auth::user();
        $employee = $user->employee;

        $balances = LeaveBalance::forEmployee($employee->id)
            ->forYear(now()->year)
            ->with('leaveType')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $balances,
            'message' => 'Leave balance retrieved successfully'
        ]);
    }

    /**
     * Get leave statistics.
     */
    public function statistics(): JsonResponse
    {
        $user = Auth::user();
        
        if (in_array($user->role, ['manager', 'hr', 'admin'])) {
            // Admin/HR/Manager can see all statistics
            $stats = [
                'total_requests' => Leave::count(),
                'pending_requests' => Leave::where('status', 'pending')->count(),
                'approved_requests' => Leave::where('status', 'approved')->count(),
                'rejected_requests' => Leave::where('status', 'rejected')->count(),
                'cancelled_requests' => Leave::where('status', 'cancelled')->count(),
                'by_type' => Leave::selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get(),
                'by_month' => Leave::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                    ->whereYear('created_at', now()->year)
                    ->groupBy('month')
                    ->get(),
            ];
        } else {
            // Employee can see their own statistics
            $employee = $user->employee;
            $stats = [
                'total_requests' => Leave::where('employee_id', $employee->id)->count(),
                'pending_requests' => Leave::where('employee_id', $employee->id)->where('status', 'pending')->count(),
                'approved_requests' => Leave::where('employee_id', $employee->id)->where('status', 'approved')->count(),
                'rejected_requests' => Leave::where('employee_id', $employee->id)->where('status', 'rejected')->count(),
                'cancelled_requests' => Leave::where('employee_id', $employee->id)->where('status', 'cancelled')->count(),
                'by_type' => Leave::selectRaw('type, COUNT(*) as count')
                    ->where('employee_id', $employee->id)
                    ->groupBy('type')
                    ->get(),
                'by_month' => Leave::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                    ->where('employee_id', $employee->id)
                    ->whereYear('created_at', now()->year)
                    ->groupBy('month')
                    ->get(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => 'Leave statistics retrieved successfully'
        ]);
    }

    // Private methods (same as web controller)
    private function createLeaveNotification(Leave $leave, string $type): void
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

    private function notifyApprovers(Leave $leave, string $type): void
    {
        $managers = \App\Models\User::where('role', 'manager')->get();
        
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

    private function createApprovalWorkflow(Leave $leave): void
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

    private function updateLeaveStatus(Leave $leave): void
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

    private function updateLeaveBalance(Leave $leave): void
    {
        $balance = LeaveBalance::forEmployee($leave->employee_id)
            ->forYear($leave->start_date->year)
            ->byType($leave->type)
            ->first();

        if ($balance) {
            $balance->updateBalance($leave->days);
        }
    }

    private function canViewLeave(Leave $leave, $user): bool
    {
        if ($leave->employee->user_id === $user->id) {
            return true;
        }

        if (in_array($user->role, ['manager', 'hr', 'admin'])) {
            return true;
        }

        return false;
    }

    private function canEditLeave(Leave $leave, $user): bool
    {
        return $leave->employee->user_id === $user->id && $leave->status === Leave::STATUS_PENDING;
    }

    private function getNotificationTitle(string $type): string
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

    private function getNotificationMessage(Leave $leave, string $type): string
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
