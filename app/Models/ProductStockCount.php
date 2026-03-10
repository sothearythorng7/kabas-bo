<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockCount extends Model
{
    protected $fillable = [
        'product_id',
        'store_id',
        'last_counted_at',
        'counted_by',
    ];

    protected $casts = [
        'last_counted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
