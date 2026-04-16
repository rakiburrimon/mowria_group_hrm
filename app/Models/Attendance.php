<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
        'late_minutes',
        'early_leave_minutes',
        'overtime_hours',
        'work_hours'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_hours' => 'decimal:2',
        'work_hours' => 'decimal:2'
    ];

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_LEAVE = 'leave';
    public const STATUS_HOLIDAY = 'holiday';
    public const STATUS_HALF_DAY = 'half_day';

    // Office hours configuration
    public const OFFICE_START_TIME = '09:00';
    public const OFFICE_END_TIME = '18:00';
    public const LATE_GRACE_MINUTES = 15;
    public const EARLY_LEAVE_GRACE_MINUTES = 15;

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopePresent($query)
    {
        return $query->where('status', self::STATUS_PRESENT);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    public function scopeLate($query)
    {
        return $query->where('status', self::STATUS_LATE);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->whereHas('employee', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Calculate work hours
    public function calculateWorkHours()
    {
        if ($this->check_in && $this->check_out) {
            $checkIn = Carbon::parse($this->date . ' ' . $this->check_in);
            $checkOut = Carbon::parse($this->date . ' ' . $this->check_out);
            
            $this->work_hours = $checkOut->diffInHours($checkIn);
            
            // Calculate overtime (after office hours)
            $officeEnd = Carbon::parse($this->date . ' ' . self::OFFICE_END_TIME);
            if ($checkOut > $officeEnd) {
                $this->overtime_hours = $checkOut->diffInHours($officeEnd);
            }
            
            $this->save();
        }
    }

    // Calculate late minutes
    public function calculateLateMinutes()
    {
        if ($this->check_in) {
            $checkIn = Carbon::parse($this->date . ' ' . $this->check_in);
            $officeStart = Carbon::parse($this->date . ' ' . self::OFFICE_START_TIME);
            $gracePeriod = $officeStart->copy()->addMinutes(self::LATE_GRACE_MINUTES);
            
            if ($checkIn > $gracePeriod) {
                $this->late_minutes = $checkIn->diffInMinutes($officeStart);
                $this->status = self::STATUS_LATE;
            } else {
                $this->late_minutes = 0;
                $this->status = self::STATUS_PRESENT;
            }
            
            $this->save();
        }
    }

    // Calculate early leave minutes
    public function calculateEarlyLeaveMinutes()
    {
        if ($this->check_out) {
            $checkOut = Carbon::parse($this->date . ' ' . $this->check_out);
            $officeEnd = Carbon::parse($this->date . ' ' . self::OFFICE_END_TIME);
            $gracePeriod = $officeEnd->copy()->subMinutes(self::EARLY_LEAVE_GRACE_MINUTES);
            
            if ($checkOut < $gracePeriod) {
                $this->early_leave_minutes = $officeEnd->diffInMinutes($checkOut);
            } else {
                $this->early_leave_minutes = 0;
            }
            
            $this->save();
        }
    }

    // Get formatted work hours
    public function getFormattedWorkHoursAttribute()
    {
        return number_format($this->work_hours, 2) . ' hours';
    }

    // Get formatted overtime hours
    public function getFormattedOvertimeHoursAttribute()
    {
        return number_format($this->overtime_hours, 2) . ' hours';
    }

    // Get late status label
    public function getLateStatusLabelAttribute()
    {
        if ($this->late_minutes > 0) {
            return 'Late by ' . $this->late_minutes . ' minutes';
        }
        return 'On Time';
    }

    // Get early leave status label
    public function getEarlyLeaveStatusLabelAttribute()
    {
        if ($this->early_leave_minutes > 0) {
            return 'Early by ' . $this->early_leave_minutes . ' minutes';
        }
        return 'Full Day';
    }

    // Get status badge color
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_PRESENT => 'success',
            self::STATUS_ABSENT => 'danger',
            self::STATUS_LATE => 'warning',
            self::STATUS_LEAVE => 'info',
            self::STATUS_HOLIDAY => 'secondary',
            self::STATUS_HALF_DAY => 'primary',
            default => 'secondary',
        };
    }

    // Get status label
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            self::STATUS_PRESENT => 'Present',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_LATE => 'Late',
            self::STATUS_LEAVE => 'On Leave',
            self::STATUS_HOLIDAY => 'Holiday',
            self::STATUS_HALF_DAY => 'Half Day',
            default => ucfirst($this->status),
        };
    }

    // Check if employee is late
    public function isLate()
    {
        return $this->late_minutes > self::LATE_GRACE_MINUTES;
    }

    // Check if employee left early
    public function leftEarly()
    {
        return $this->early_leave_minutes > self::EARLY_LEAVE_GRACE_MINUTES;
    }

    // Get attendance summary for employee in a month
    public static function getMonthlySummary($employeeId, $year, $month)
    {
        $attendances = self::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', self::STATUS_PRESENT)->count(),
            'late_days' => $attendances->where('status', self::STATUS_LATE)->count(),
            'absent_days' => $attendances->where('status', self::STATUS_ABSENT)->count(),
            'leave_days' => $attendances->where('status', self::STATUS_LEAVE)->count(),
            'total_work_hours' => $attendances->sum('work_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_early_leave_minutes' => $attendances->sum('early_leave_minutes'),
        ];
    }
}
