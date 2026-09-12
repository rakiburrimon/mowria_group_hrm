<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Traits\FileUploadTrait;
use App\Traits\LogsActions;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Service layer for employee operations.
 *
 * Handles business logic, file uploads, logging and activity tracking.
 * Database access goes through the repository layer.
 */
class EmployeeService
{
    use FileUploadTrait, LogsActions;

    public function __construct(
        protected EmployeeRepositoryInterface $employees
    ) {}

    /**
     * Paginate employees with optional filters.
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Employee::with(['department', 'user']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['position'])) {
            $query->where('position', 'like', "%{$filters['position']}%");
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new employee, optionally with a profile image.
     */
    public function create(array $data, ?UploadedFile $image = null): Employee
    {
        if ($image) {
            $data['profile_image'] = $this->uploadFile($image, 'employees/profiles');
        }

        $employee = $this->employees->create($data);

        $this->logAction('employee created', $employee);

        return $employee;
    }

    /**
     * Update an existing employee, optionally with a new profile image.
     */
    public function update(Employee $employee, array $data, ?UploadedFile $image = null): bool
    {
        if ($image) {
            if ($employee->profile_image) {
                $this->deleteFile($employee->profile_image);
            }
            $data['profile_image'] = $this->uploadFile($image, 'employees/profiles');
        }

        $updated = $employee->update($data);

        $this->logAction('employee updated', $employee);

        return $updated;
    }

    /**
     * Delete an employee and remove the profile image.
     */
    public function delete(Employee $employee): bool
    {
        if ($employee->profile_image) {
            $this->deleteFile($employee->profile_image);
        }

        $deleted = $employee->delete();

        $this->logAction('employee deleted', $employee);

        return $deleted;
    }

    /**
     * Update the employee status.
     */
    public function updateStatus(Employee $employee, string $status): bool
    {
        $updated = $employee->update(['status' => $status]);

        $this->logAction('employee status updated', $employee, ['status' => $status]);

        return $updated;
    }

    /**
     * Upload a new profile image for the employee.
     */
    public function uploadProfileImage(Employee $employee, UploadedFile $image): array
    {
        if ($employee->profile_image) {
            $this->deleteFile($employee->profile_image);
        }

        $profileImage = $this->uploadFile($image, 'employees/profiles');
        $employee->update(['profile_image' => $profileImage]);

        $thumbnail = $this->createThumbnail($profileImage, 100, 100);

        $this->logAction('employee profile image updated', $employee);

        return [
            'profile_image' => $profileImage,
            'profile_image_url' => $this->getFileUrl($profileImage),
            'thumbnail' => $thumbnail,
            'thumbnail_url' => $thumbnail ? $this->getFileUrl($thumbnail) : null,
            'dimensions' => $this->getImageDimensions($profileImage),
        ];
    }

    /**
     * Get employee statistics for dashboards.
     */
    public function statistics(): array
    {
        return [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'inactive' => Employee::inactive()->count(),
            'terminated' => Employee::where('status', Employee::STATUS_TERMINATED)->count(),
            'by_department' => Employee::with('department')
                ->get()
                ->groupBy('department.name')
                ->map(fn ($group) => $group->count()),
            'recent_hires' => Employee::where('hire_date', '>=', now()->subDays(30))
                ->orderBy('hire_date', 'desc')
                ->take(5)
                ->get(['id', 'first_name', 'last_name', 'hire_date']),
        ];
    }
}
