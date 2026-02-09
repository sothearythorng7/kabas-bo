<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Shift;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id', 'store_id', 'pos_local_id', 'payment_type', 'total', 'discounts', 'split_payments',
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

    /**
     * Get the real revenue (excluding voucher payments).
     * Vouchers have already been counted when created (from exchange or manual).
     */
    public function getRealRevenueAttribute(): float
    {
        // If 100% voucher payment (no split)
        if ($this->payment_type === 'VOUCHER' && empty($this->split_payments)) {
            return 0;
        }

        // If split payments exist, sum only non-voucher amounts
        if (!empty($this->split_payments) && is_array($this->split_payments)) {
            $realAmount = 0;
            foreach ($this->split_payments as $payment) {
                if (strtoupper($payment['payment_type'] ?? '') !== 'VOUCHER') {
                    $realAmount += (float) ($payment['amount'] ?? 0);
                }
            }
            return $realAmount;
        }

        // Regular payment (no voucher)
        return (float) $this->total;
    }

    /**
     * Calculate sum of real revenue for a collection of sales (excluding voucher amounts).
     */
    public static function sumRealRevenue($sales): float
    {
        $total = 0;
        foreach ($sales as $sale) {
            $total += $sale->real_revenue;
        }
        return $total;
    }

    /**
     * Scope to exclude 100% voucher sales (for counting purposes).
     */
    public function scopeExcludeFullVoucher($query)
    {
        return $query->where(function ($q) {
            $q->where('payment_type', '!=', 'VOUCHER')
              ->orWhereNotNull('split_payments');
        });
    }
}

