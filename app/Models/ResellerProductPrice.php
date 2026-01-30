<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerProductPrice extends Model
{
    protected $fillable = [
        'reseller_id',
        'product_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the reseller that owns this price.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Get the product that this price is for.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the B2B price for a specific reseller and product.
     * Falls back to product's price_btob or price if no custom price exists.
     */
    public static function getPriceFor(int $resellerId, int $productId): ?float
    {
        $customPrice = static::where('reseller_id', $resellerId)
            ->where('product_id', $productId)
            ->value('price');

        if ($customPrice !== null) {
            return (float) $customPrice;
        }

        // Fallback to product's price_btob or price
        $product = Product::find($productId);
        if ($product) {
            return (float) ($product->price_btob ?? $product->price);
        }

        return null;
    }

    /**
     * Set or update the B2B price for a specific reseller and product.
     */
    public static function setPriceFor(int $resellerId, int $productId, float $price): self
    {
        return static::updateOrCreate(
            ['reseller_id' => $resellerId, 'product_id' => $productId],
            ['price' => $price]
        );
    }
}
