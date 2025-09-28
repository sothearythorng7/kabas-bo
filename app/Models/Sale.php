<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id', 'payment_type', 'total', 'discounts', 'synced_at'
    ];

    protected $casts = [
        'discounts' => 'array',
        'synced_at' => 'datetime',
    ];

    public function shift() {
        return $this->belongsTo(Shift::class);
    }

    public function items() {
        return $this->hasMany(SaleItem::class);
    }
}
