<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'store_id', 'opening_cash', 'closing_cash', 'visitors_count', 'cash_difference', 'cash_in', 'cash_out', 'started_at', 'ended_at', 'synced'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'synced' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function shiftUsers()
    {
        return $this->hasMany(ShiftUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'shift_users')
            ->withPivot('started_at', 'ended_at')
            ->withTimestamps();
    }
}
