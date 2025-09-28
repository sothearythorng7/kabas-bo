<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $fillable = [
        'stock_batch_id',
        'store_id',
        'product_id',
        'type',
        'quantity',
        'reason',
        'sale_id',
        'shift_id',
    ];

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
