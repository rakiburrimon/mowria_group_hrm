<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UploadProfileImageRequest;
use App\Models\Employee;
use App\Models\Department;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    use FileUploadTrait;

    /**
     * Display a listing of employees.
     */
    public function index(Request $request): View
    {
        $query = Employee::with(['department', 'user']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('position')) {
            $query->where('position', 'like', "%{$request->get('position')}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $employees = $query->paginate(10)->withQueryString();

        $departments = Department::active()->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): View
    {
        $departments = Department::active()->get();
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
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $validation = $this->validateFileUpload($request->file('profile_image'));
                
                if (!$validation['valid']) {
                    return redirect()->back()
                        ->withErrors(['profile_image' => $validation['errors']])
                        ->withInput();
                }

                $data['profile_image'] = $this->uploadFile(
                    $request->file('profile_image'),
                    'employees/profiles'
                );
            }

            $employee = Employee::create($data);

            DB::commit();

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()])
                ->withInput();
        }
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
        $departments = Department::active()->get();
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
        try {
            DB::beginTransaction();

            $data = $request->validated();
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $validation = $this->validateFileUpload($request->file('profile_image'));
                
                if (!$validation['valid']) {
                    return redirect()->back()
                        ->withErrors(['profile_image' => $validation['errors']])
                        ->withInput();
                }

                // Delete old image if exists
                if ($employee->profile_image) {
                    $this->deleteFile($employee->profile_image);
                }

                $data['profile_image'] = $this->uploadFile(
                    $request->file('profile_image'),
                    'employees/profiles'
                );
            }

            $employee->update($data);

            DB::commit();

            return redirect()
                ->route('employees.show', $employee->id)
                ->with('success', 'Employee updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Delete profile image if exists
            if ($employee->profile_image) {
                $this->deleteFile($employee->profile_image);
            }

            $employee->delete();

            DB::commit();

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload profile image for employee
     */
    public function uploadProfileImage(UploadProfileImageRequest $request, Employee $employee): JsonResponse
    {
        try {
            $validation = $this->validateFileUpload($request->file('profile_image'));
            
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file',
                    'errors' => $validation['errors']
                ], 422);
            }

            // Delete old image if exists
            if ($employee->profile_image) {
                $this->deleteFile($employee->profile_image);
            }

            $profileImage = $this->uploadFile(
                $request->file('profile_image'),
                'employees/profiles'
            );

            $employee->update(['profile_image' => $profileImage]);

            // Create thumbnail
            $thumbnail = $this->createThumbnail($profileImage, 100, 100);

            return response()->json([
                'success' => true,
                'message' => 'Profile image uploaded successfully',
                'data' => [
                    'profile_image' => $profileImage,
                    'profile_image_url' => $this->getFileUrl($profileImage),
                    'thumbnail' => $thumbnail,
                    'thumbnail_url' => $thumbnail ? $this->getFileUrl($thumbnail) : null,
                    'dimensions' => $this->getImageDimensions($profileImage),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload profile image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => Employee::count(),
                'active' => Employee::active()->count(),
                'inactive' => Employee::inactive()->count(),
                'terminated' => Employee::where('status', Employee::STATUS_TERMINATED)->count(),
                'by_department' => Employee::with('department')
                    ->get()
                    ->groupBy('department.name')
                    ->map(function ($group) {
                        return $group->count();
                    }),
                'recent_hires' => Employee::where('hire_date', '>=', now()->subDays(30))
                    ->orderBy('hire_date', 'desc')
                    ->take(5)
                    ->get(['id', 'first_name', 'last_name', 'hire_date']),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus(Request $request, Employee $employee): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:' . implode(',', [
                    Employee::STATUS_ACTIVE,
                    Employee::STATUS_INACTIVE,
                    Employee::STATUS_TERMINATED
                ])
            ]);

            $employee->update(['status' => $request->get('status')]);

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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees as JSON for API
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $query = Employee::with(['department', 'user']);

        // Apply same filters as web version
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $employees = $query->paginate($request->get('per_page', 15));

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
    }

    /**
     * Get single employee for API
     */
    public function apiShow(Employee $employee): JsonResponse
    {
        $employee->load(['department', 'user', 'attendances', 'leaves']);

        return response()->json([
            'success' => true,
            'data' => $employee
        ]);
    }
}
