<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    protected $fillable = [
        'pin',
        'punch_time',
        'status',
        'verify',
        'workcode',
        'device_sn',
        'processed',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'processed' => 'boolean',
    ];
}
