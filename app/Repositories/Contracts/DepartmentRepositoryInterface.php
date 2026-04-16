<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepartmentRepositoryInterface
{
    public function all(): LengthAwarePaginator;
    
    public function find(int $id): ?Department;
    
    public function create(array $data): Department;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function active(): \Illuminate\Database\Eloquent\Collection;
    
    public function search(string $term): LengthAwarePaginator;
}
