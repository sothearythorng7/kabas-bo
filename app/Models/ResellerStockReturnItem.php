<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerStockReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_stock_return_id',
        'product_id',
        'quantity',
        'reason',
    ];

    public function stockReturn()
    {
        return $this->belongsTo(ResellerStockReturn::class, 'reseller_stock_return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
