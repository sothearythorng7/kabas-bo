<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsPosition extends Model
{
    protected $fillable = [
        'device_id', 'device_name', 'latitude', 'longitude',
        'speed', 'heading', 'altitude', 'satellites',
        'gps_fixed', 'acc_on', 'battery_level', 'alarm_type', 'device_time',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'gps_fixed' => 'boolean',
        'acc_on' => 'boolean',
        'device_time' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(GpsDevice::class, 'device_id', 'device_id');
    }
}
