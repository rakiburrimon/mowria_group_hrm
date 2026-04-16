<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type',
        'total_days',
        'used_days',
        'remaining_days',
        'year',
        'carry_over',
        'notes'
    ];

    protected $casts = [
        'total_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'remaining_days' => 'decimal:2',
        'carry_over' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    public function updateBalance($usedDays)
    {
        $this->used_days += $usedDays;
        $this->remaining_days = max(0, $this->total_days - $this->used_days);
        $this->save();
    }

    public function checkBalance($requestedDays)
    {
        return $this->remaining_days >= $requestedDays;
    }
}
