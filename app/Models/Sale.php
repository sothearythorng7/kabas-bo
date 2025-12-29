<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id', 'store_id', 'payment_type', 'total', 'discounts', 'split_payments',
        'synced_at', 'financial_transaction_id'
    ];

    protected $casts = [
        'discounts' => 'array',
        'split_payments' => 'array',
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

    public function exchanges()
    {
        return $this->hasMany(Exchange::class, 'original_sale_id');
    }

    public function exchangeAsNew()
    {
        return $this->hasOne(Exchange::class, 'new_sale_id');
    }

    public function vouchersUsed()
    {
        return $this->hasMany(Voucher::class, 'used_in_sale_id');
    }
}

