<?php

namespace App\Repositories\Contracts;

use App\Models\Leave;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

interface LeaveRepositoryInterface
{
    public function all(): LengthAwarePaginator;
    
    public function find(int $id): ?Leave;
    
    public function create(array $data): Leave;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function pending(): \Illuminate\Database\Eloquent\Collection;
    
    public function byEmployee(int $employeeId): LengthAwarePaginator;
    
    public function byDateRange(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection;
    
    public function getLeaveStats(Carbon $startDate, Carbon $endDate): array;
    
    public function getLeaveBalance(int $employeeId, int $year): array;
    
    public function approve(int $id, int $approvedBy): bool;
    
    public function reject(int $id, int $approvedBy, string $remarks): bool;
}
