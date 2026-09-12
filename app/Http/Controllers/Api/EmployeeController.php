<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UploadProfileImageRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected EmployeeService $employees
    ) {}

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $employees = $this->employees->paginate($request->all(), $request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $employees->items(),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem(),
                ]
            ]);
        }, 'Failed to fetch employees');
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        return $this->handleService(function () use ($request) {
            $employee = $this->employees->create(
                $request->validated(),
                $request->file('profile_image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully',
                'data' => $employee
            ], 201);
        }, 'Failed to create employee');
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($employee) {
            $employee->load(['department', 'user', 'attendances', 'leaves']);

            return response()->json([
                'success' => true,
                'data' => $employee
            ]);
        }, 'Failed to fetch employee');
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($request, $employee) {
            $this->employees->update(
                $employee,
                $request->validated(),
                $request->file('profile_image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee
            ]);
        }, 'Failed to update employee');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($employee) {
            $this->employees->delete($employee);

            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully'
            ]);
        }, 'Failed to delete employee');
    }

    /**
     * Upload profile image for employee
     */
    public function uploadProfileImage(UploadProfileImageRequest $request, Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($request, $employee) {
            $data = $this->employees->uploadProfileImage(
                $employee,
                $request->file('profile_image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'data' => $data
            ]);
        }, 'Failed to upload profile image');
    }

    /**
     * Update employee status
     */
    public function updateStatus(\App\Http\Requests\Employee\UpdateEmployeeStatusRequest $request, Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($request, $employee) {
            $this->employees->updateStatus($employee, $request->validated('status'));

            return response()->json([
                'success' => true,
                'message' => 'Employee status updated successfully',
                'data' => [
                    'id' => $employee->id,
                    'status' => $employee->status,
                    'status_label' => match($employee->status) {
                        Employee::STATUS_ACTIVE => 'Active',
                        Employee::STATUS_INACTIVE => 'Inactive',
                        Employee::STATUS_TERMINATED => 'Terminated',
                    }
                ]
            ]);
        }, 'Failed to update status');
    }

    /**
     * Get employee statistics
     */
    public function statistics(): JsonResponse
    {
        return $this->handleService(function () {
            return response()->json([
                'success' => true,
                'data' => $this->employees->statistics()
            ]);
        }, 'Failed to get statistics');
    }
}
