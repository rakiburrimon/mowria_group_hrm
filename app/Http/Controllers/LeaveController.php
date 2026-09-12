<?php

namespace App\Http\Controllers;

use App\DataTables\LeaveApprovalsDataTable;
use App\DataTables\LeavesDataTable;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\DepartmentService;
use App\Services\LeaveService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected LeaveService $leaves,
        protected DepartmentService $departments
    ) {}

    /**
     * Display a listing of leaves for the authenticated employee.
     */
    public function index(LeavesDataTable $dataTable)
    {
        $employee = Auth::user()->employee;
        $leaveTypes = LeaveType::active()->get();
        $leaveBalances = $this->leaves->getLeaveBalance($employee);

        return $dataTable->render('leaves.index', compact('leaveTypes', 'leaveBalances'));
    }

    /**
     * Show the form for applying leave.
     */
    public function create(): View
    {
        $employee = Auth::user()->employee;
        $leaveTypes = LeaveType::active()->get();
        $leaveBalances = $this->leaves->getLeaveBalance($employee);

        return view('leaves.create', compact('leaveTypes', 'leaveBalances'));
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
    public function show(Leave $leave): View
    {
        $leave->load(['employee', 'approvals.approver', 'approvedBy']);

        $user = Auth::user();
        if (!$this->canViewLeave($leave, $user)) {
            abort(403, 'Unauthorized access');
        }

        return view('leaves.show', compact('leave'));
    }

    /**
     * Show the form for editing the specified leave.
     */
    public function edit(Leave $leave): View
    {
        if ($leave->status !== Leave::STATUS_PENDING) {
            abort(403, 'Cannot edit processed leave requests');
        }

        $user = Auth::user();
        if (!$this->canEditLeave($leave, $user)) {
            abort(403, 'Unauthorized access');
        }

        $leave->load(['employee', 'approvals']);
        $leaveTypes = LeaveType::active()->get();

        return view('leaves.edit', compact('leave', 'leaveTypes'));
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
     * Display admin approval panel.
     */
    public function approvalPanel(LeaveApprovalsDataTable $dataTable)
    {
        $departments = $this->departments->active();
        $leaveTypes = LeaveType::active()->get();

        return $dataTable->render('leaves.approval-panel', compact('departments', 'leaveTypes'));
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
                'data' => $balances
            ]);
        }, 'Failed to fetch leave balance');
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
