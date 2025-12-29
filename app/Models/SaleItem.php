<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'price', 'discounts', 'is_delivery', 'delivery_address', 'is_custom_service', 'custom_service_description',
        'exchanged_at', 'exchanged_in_exchange_id', 'added_via_exchange_id'
    ];

    protected $casts = [
        'discounts' => 'array',
        'is_delivery' => 'boolean',
        'is_custom_service' => 'boolean',
        'exchanged_at' => 'datetime',
    ];

    public function sale() {
        return $this->belongsTo(Sale::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function exchangedInExchange()
    {
        return $this->belongsTo(Exchange::class, 'exchanged_in_exchange_id');
    }

    /**
     * Check if this item can be exchanged
     */
    public function isExchangeable(): bool
    {
        return is_null($this->exchanged_at);
    }
}
