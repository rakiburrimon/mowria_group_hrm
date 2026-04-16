<?php

namespace App\Repositories\Contracts;

use App\Models\Attendance;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

interface AttendanceRepositoryInterface
{
    public function all(): LengthAwarePaginator;
    
    public function find(int $id): ?Attendance;
    
    public function create(array $data): Attendance;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function byDate(Carbon $date): \Illuminate\Database\Eloquent\Collection;
    
    public function byEmployee(int $employeeId): LengthAwarePaginator;
    
    public function byDateRange(Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection;
    
    public function getAttendanceStats(Carbon $startDate, Carbon $endDate): array;
    
    public function getTodayAttendance(): \Illuminate\Database\Eloquent\Collection;
    
    public function getMonthlyAttendance(int $year, int $month): \Illuminate\Database\Eloquent\Collection;
}
