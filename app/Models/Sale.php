<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id', 'store_id', 'payment_type', 'total', 'discounts', 'synced_at', 'financial_transaction_id'
    ];

    protected $casts = [
        'discounts' => 'array',
        'synced_at' => 'datetime',
    ];

    public function shift() {
        return $this->belongsTo(Shift::class);
    }

    public function store() {
        return $this->belongsTo(Store::class);
    }

    public function items() {
        return $this->hasMany(SaleItem::class);
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}

