<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'store_id', 'opening_cash', 'closing_cash', 'started_at', 'ended_at', 'synced'
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
}
