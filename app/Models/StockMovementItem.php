<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovementItem extends Model
{
    protected $fillable = ['stock_movement_id', 'product_id', 'quantity'];

    public function movement()
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
