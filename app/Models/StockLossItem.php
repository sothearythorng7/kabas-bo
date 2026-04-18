<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLossItem extends Model
{
    protected $fillable = [
        'stock_loss_id', 'product_id', 'quantity', 'unit_cost', 'loss_reason',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:5',
    ];

    public function stockLoss()
    {
        return $this->belongsTo(StockLoss::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * ($this->unit_cost ?? 0);
    }
}
