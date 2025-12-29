<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExchangeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_id',
        'original_sale_item_id',
        'new_sale_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'stock_batch_id',
        'type', // 'returned' or 'new'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships

    public function exchange()
    {
        return $this->belongsTo(Exchange::class);
    }

    public function originalSaleItem()
    {
        return $this->belongsTo(SaleItem::class, 'original_sale_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch()
    {
        return $this->belongsTo(StockBatch::class);
    }
}
