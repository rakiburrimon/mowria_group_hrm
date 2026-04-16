<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'related_id',
        'related_type',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public const TYPE_LEAVE_APPLIED = 'leave_applied';
    public const TYPE_LEAVE_APPROVED = 'leave_approved';
    public const TYPE_LEAVE_REJECTED = 'leave_rejected';
    public const TYPE_LEAVE_CANCELLED = 'leave_cancelled';
    public const TYPE_LEAVE_UPDATED = 'leave_updated';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            self::TYPE_LEAVE_APPLIED => 'Leave Applied',
            self::TYPE_LEAVE_APPROVED => 'Leave Approved',
            self::TYPE_LEAVE_REJECTED => 'Leave Rejected',
            self::TYPE_LEAVE_CANCELLED => 'Leave Cancelled',
            self::TYPE_LEAVE_UPDATED => 'Leave Updated',
            default => 'Unknown',
        };
    }

    public function getIconAttribute()
    {
        return match($this->type) {
            self::TYPE_LEAVE_APPLIED => 'fas fa-calendar-plus',
            self::TYPE_LEAVE_APPROVED => 'fas fa-check-circle',
            self::TYPE_LEAVE_REJECTED => 'fas fa-times-circle',
            self::TYPE_LEAVE_CANCELLED => 'fas fa-calendar-times',
            self::TYPE_LEAVE_UPDATED => 'fas fa-calendar-edit',
            default => 'fas fa-bell',
        };
    }

    public function getColorAttribute()
    {
        return match($this->type) {
            self::TYPE_LEAVE_APPLIED => 'info',
            self::TYPE_LEAVE_APPROVED => 'success',
            self::TYPE_LEAVE_REJECTED => 'danger',
            self::TYPE_LEAVE_CANCELLED => 'warning',
            self::TYPE_LEAVE_UPDATED => 'primary',
            default => 'secondary',
        };
    }
}
