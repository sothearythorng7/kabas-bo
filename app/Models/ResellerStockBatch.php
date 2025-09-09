<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerStockBatch extends Model
{
    protected $fillable = [
        'reseller_id',
        'product_id',
        'quantity',
        'unit_price',
        'source_delivery_id',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function delivery()
    {
        return $this->belongsTo(ResellerStockDelivery::class, 'source_delivery_id');
    }
}
