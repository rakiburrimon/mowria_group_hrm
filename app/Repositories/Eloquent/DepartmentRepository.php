<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    protected Department $model;

    public function __construct(Department $model)
    {
        $this->model = $model;
    }

    public function all(): LengthAwarePaginator
    {
        return $this->model->withCount('employees')
            ->orderBy('name')
            ->paginate(10);
    }

    public function find(int $id): ?Department
    {
        return $this->model->with('employees')->find($id);
    }

    public function create(array $data): Department
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $department = $this->find($id);
        return $department ? $department->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $department = $this->find($id);
        return $department ? $department->delete() : false;
    }

    public function active(): Collection
    {
        return $this->model->active()->get();
    }

    public function search(string $term): LengthAwarePaginator
    {
        return $this->model->where('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(10);
    }
}
