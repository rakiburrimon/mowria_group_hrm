<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    public function all(): LengthAwarePaginator;
    
    public function find(int $id): ?Employee;
    
    public function create(array $data): Employee;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function active(): \Illuminate\Database\Eloquent\Collection;
    
    public function byDepartment(int $departmentId): LengthAwarePaginator;
    
    public function search(string $term): LengthAwarePaginator;
    
    public function withRelations(): LengthAwarePaginator;
    
    public function getByUser(int $userId): ?Employee;
}
