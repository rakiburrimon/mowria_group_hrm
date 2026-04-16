<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'max_days_per_year',
        'requires_approval',
        'allow_carry_over',
        'is_active',
        'color_code'
    ];

    protected $casts = [
        'max_days_per_year' => 'decimal:2',
        'requires_approval' => 'boolean',
        'allow_carry_over' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'type');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'leave_type');
    }
}
