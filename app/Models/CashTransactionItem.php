<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cashTransaction()
    {
        return $this->belongsTo(CashTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
