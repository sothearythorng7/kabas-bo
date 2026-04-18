<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsDevice extends Model
{
    protected $fillable = ['device_id', 'name', 'model', 'sim_number', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function positions()
    {
        return $this->hasMany(GpsPosition::class, 'device_id', 'device_id');
    }

    public function latestPosition()
    {
        return $this->hasOne(GpsPosition::class, 'device_id', 'device_id')->latestOfMany();
    }
}
