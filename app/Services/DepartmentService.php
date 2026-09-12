<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Traits\LogsActions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service layer for department operations.
 *
 * Wraps repository calls and logs activity for each write action.
 */
class DepartmentService
{
    use LogsActions;

    public function __construct(
        protected DepartmentRepositoryInterface $departments
    ) {}

    /**
     * Get all departments paginated.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->departments->all();
    }

    /**
     * Get active departments.
     */
    public function active(): Collection
    {
        return $this->departments->active();
    }

    /**
     * Search departments by term.
     */
    public function search(string $term): LengthAwarePaginator
    {
        return $this->departments->search($term);
    }

    /**
     * Find a department by ID.
     */
    public function find(int $id): ?Department
    {
        return $this->departments->find($id);
    }

    /**
     * Create a new department.
     */
    public function create(array $data): Department
    {
        $department = $this->departments->create($data);

        $this->logAction('department created', $department);

        return $department;
    }

    /**
     * Update an existing department.
     */
    public function update(Department $department, array $data): bool
    {
        $updated = $department->update($data);

        $this->logAction('department updated', $department);

        return $updated;
    }

    /**
     * Delete a department.
     */
    public function delete(Department $department): bool
    {
        $deleted = $department->delete();

        $this->logAction('department deleted', $department);

        return $deleted;
    }
}
