<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Models\Leave;
use App\Services\LeaveService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected LeaveService $leaves
    ) {}

    /**
     * Display a listing of leaves for the authenticated employee.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $leaves = $this->leaves->paginateForEmployee(
                Auth::user()->employee,
                $request->all(),
                15
            );

            return response()->json([
                'success' => true,
                'data' => $leaves,
                'message' => 'Leave requests retrieved successfully'
            ]);
        }, 'Failed to fetch leave requests');
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $leave = $this->leaves->create(
                Auth::user()->employee,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $leave
            ]);
        }, 'Failed to submit leave request');
    }

    /**
     * Display the specified leave.
     */
    public function show(Leave $leave): JsonResponse
    {
        return $this->handleService(function () use ($leave) {
            $leave->load(['employee', 'approvals.approver', 'approvedBy']);

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
        }, 'Failed to fetch leave request');
    }

    /**
     * Update the specified leave in storage.
     */
    public function update(UpdateLeaveRequest $request, Leave $leave): JsonResponse
    {
        return $this->handleService(function () use ($request, $leave) {
            if ($leave->status !== Leave::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update processed leave requests'
                ], 403);
            }

            $user = Auth::user();
            if (!$this->canEditLeave($leave, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->leaves->update($leave, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Leave request updated successfully',
                'data' => $leave
            ]);
        }, 'Failed to update leave request');
    }

    /**
     * Remove the specified leave from storage.
     */
    public function destroy(Leave $leave): JsonResponse
    {
        return $this->handleService(function () use ($leave) {
            if ($leave->status !== Leave::STATUS_PENDING) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel processed leave requests'
                ], 403);
            }

            $user = Auth::user();
            if (!$this->canEditLeave($leave, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $this->leaves->cancel($leave);

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully'
            ]);
        }, 'Failed to cancel leave request');
    }

    /**
     * Get pending leave requests for approval panel.
     */
    public function approvalPanel(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $leaves = $this->leaves->paginatePending($request->all(), 20);

            return response()->json([
                'success' => true,
                'data' => $leaves,
                'message' => 'Pending leave requests retrieved successfully'
            ]);
        }, 'Failed to fetch pending leave requests');
    }

    /**
     * Approve or reject leave request.
     */
    public function approve(ApproveLeaveRequest $request, Leave $leave): JsonResponse
    {
        return $this->handleService(function () use ($request, $leave) {
            $approval = $this->leaves->approve(
                $leave,
                $request->validated(),
                Auth::user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Leave request ' . $request->get('status') . ' successfully',
                'data' => $approval
            ]);
        }, 'Failed to process leave request');
    }

    /**
     * Get leave balance for employee.
     */
    public function leaveBalance(): JsonResponse
    {
        return $this->handleService(function () {
            $balances = $this->leaves->getLeaveBalance(Auth::user()->employee);

            return response()->json([
                'success' => true,
                'data' => $balances,
                'message' => 'Leave balance retrieved successfully'
            ]);
        }, 'Failed to fetch leave balance');
    }

    /**
     * Get leave statistics.
     */
    public function statistics(): JsonResponse
    {
        return $this->handleService(function () {
            $user = Auth::user();

            if ($user->hasAnyRole(['manager', 'hr', 'admin', 'super_admin'])) {
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
        }, 'Failed to fetch leave statistics');
    }

    /**
     * Check if user can view leave.
     */
    private function canViewLeave(Leave $leave, $user): bool
    {
        if ($leave->employee->user_id === $user->id) {
            return true;
        }

        if ($user->hasAnyRole(['manager', 'hr', 'admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can edit leave.
     */
    private function canEditLeave(Leave $leave, $user): bool
    {
        return $leave->employee->user_id === $user->id
            && $leave->status === Leave::STATUS_PENDING;
    }
}
