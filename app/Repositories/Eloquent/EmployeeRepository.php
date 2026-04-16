<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    protected Employee $model;

    public function __construct(Employee $model)
    {
        $this->model = $model;
    }

    public function all(): LengthAwarePaginator
    {
        return $this->model->with(['user', 'department'])
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function find(int $id): ?Employee
    {
        return $this->model->with(['user', 'department', 'attendances', 'leaves'])->find($id);
    }

    public function create(array $data): Employee
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $employee = $this->find($id);
        return $employee ? $employee->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $employee = $this->find($id);
        return $employee ? $employee->delete() : false;
    }

    public function active(): Collection
    {
        return $this->model->active()->with(['user', 'department'])->get();
    }

    public function byDepartment(int $departmentId): LengthAwarePaginator
    {
        return $this->model->byDepartment($departmentId)
            ->with(['user', 'department'])
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function search(string $term): LengthAwarePaginator
    {
        return $this->model->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('employee_id', 'like', "%{$term}%")
            ->orWhere('position', 'like', "%{$term}%")
            ->with(['user', 'department'])
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function withRelations(): LengthAwarePaginator
    {
        return $this->model->with(['user', 'department', 'attendances', 'leaves'])
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function getByUser(int $userId): ?Employee
    {
        return $this->model->where('user_id', $userId)
            ->with(['department', 'attendances', 'leaves'])
            ->first();
    }
}
