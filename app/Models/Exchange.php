<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exchange extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_sale_id',
        'store_id',
        'user_id',
        'return_total',
        'new_items_total',
        'balance',
        'payment_method',
        'payment_amount',
        'payment_voucher_id',
        'generated_voucher_id',
        'financial_transaction_id',
        'new_sale_id',
        'notes',
    ];

    protected $casts = [
        'return_total' => 'decimal:2',
        'new_items_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_amount' => 'decimal:2',
    ];

    // Relationships

    public function originalSale()
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ExchangeItem::class);
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(Voucher::class, 'payment_voucher_id');
    }

    public function generatedVoucher()
    {
        return $this->belongsTo(Voucher::class, 'generated_voucher_id');
    }

    public function newSale()
    {
        return $this->belongsTo(Sale::class, 'new_sale_id');
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
