<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceUser extends Model
{
    protected $fillable = [
        'pin',
        'name',
        'privilege',
        'password',
        'card',
        'group',
        'device_sn',
    ];
}
