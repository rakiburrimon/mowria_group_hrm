<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UploadProfileImageRequest;
use App\Models\Employee;
use App\Services\DepartmentService;
use App\Services\EmployeeService;
use App\Traits\HandlesServiceExceptions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected EmployeeService $employees,
        protected DepartmentService $departments
    ) {}

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): View
    {
        $employees = $this->employees->paginate($request->all());
        $departments = $this->departments->active();

        return view('employees.index', compact('employees', 'departments'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $departments = $this->departments->active();
        $statuses = [
            Employee::STATUS_ACTIVE => 'Active',
            Employee::STATUS_INACTIVE => 'Inactive',
            Employee::STATUS_TERMINATED => 'Terminated',
        ];

        return view('employees.create', compact('departments', 'statuses'));
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        return $this->handleService(function () use ($request) {
            $employee = $this->employees->create(
                $request->validated(),
                $request->file('profile_image')
            );

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee created successfully.');
        }, 'Failed to create employee');
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): View
    {
        $employee->load(['department', 'user', 'attendances', 'leaves']);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): View
    {
        $departments = $this->departments->active();
        $statuses = [
            Employee::STATUS_ACTIVE => 'Active',
            Employee::STATUS_INACTIVE => 'Inactive',
            Employee::STATUS_TERMINATED => 'Terminated',
        ];

        return view('employees.edit', compact('employee', 'departments', 'statuses'));
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        return $this->handleService(function () use ($request, $employee) {
            $this->employees->update(
                $employee,
                $request->validated(),
                $request->file('profile_image')
            );

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee updated successfully.');
        }, 'Failed to update employee');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        return $this->handleService(function () use ($employee) {
            $this->employees->delete($employee);

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee deleted successfully.');
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

    /**
     * Update employee status
     */
    public function updateStatus(Request $request, Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($request, $employee) {
            $request->validate([
                'status' => 'required|in:' . implode(',', [
                    Employee::STATUS_ACTIVE,
                    Employee::STATUS_INACTIVE,
                    Employee::STATUS_TERMINATED
                ])
            ]);

            $this->employees->updateStatus($employee, $request->get('status'));

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
     * Get employees as JSON for API
     */
    public function apiIndex(Request $request): JsonResponse
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
     * Get single employee for API
     */
    public function apiShow(Employee $employee): JsonResponse
    {
        return $this->handleService(function () use ($employee) {
            $employee->load(['department', 'user', 'attendances', 'leaves']);

            return response()->json([
                'success' => true,
                'data' => $employee
            ]);
        }, 'Failed to fetch employee');
    }
}
