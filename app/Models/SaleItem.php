<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'item_type', 'gift_box_id', 'gift_card_id', 'generated_gift_card_code_id',
        'quantity', 'price', 'discounts', 'is_delivery', 'delivery_address', 'is_custom_service', 'custom_service_description',
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

    public function giftBox() {
        return $this->belongsTo(GiftBox::class);
    }

    public function giftCard() {
        return $this->belongsTo(GiftCard::class);
    }

    public function generatedGiftCardCode() {
        return $this->belongsTo(GiftCardCode::class, 'generated_gift_card_code_id');
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

    /**
     * Get the net revenue for this item after all discounts (item-level + sale-level).
     * Requires 'sale' (and ideally 'sale.items') to be eager loaded for sale-level discounts.
     */
    public function getNetRevenueAttribute(): float
    {
        $gross = $this->price * $this->quantity;

        // Apply item-level discounts
        $itemDiscount = $this->calculateItemLevelDiscount();
        $net = $gross - $itemDiscount;

        // Apply proportional share of sale-level discounts
        $net -= $this->calculateSaleLevelDiscountShare();

        return round(max(0, $net), 2);
    }

    private function calculateItemLevelDiscount(): float
    {
        $gross = $this->price * $this->quantity;
        $discount = 0;

        foreach ($this->discounts ?? [] as $d) {
            if (($d['type'] ?? '') === 'amount') {
                if (($d['scope'] ?? 'line') === 'unit') {
                    $discount += ($d['value'] ?? 0) * $this->quantity;
                } else {
                    $discount += $d['value'] ?? 0;
                }
            } elseif (($d['type'] ?? '') === 'percent') {
                $discount += (($d['value'] ?? 0) / 100) * $gross;
            }
        }

        return $discount;
    }

    private function calculateSaleLevelDiscountShare(): float
    {
        $sale = $this->sale;
        if (!$sale || empty($sale->discounts)) {
            return 0;
        }

        $saleItems = $sale->items;
        $totalGross = $saleItems->sum(fn($i) => $i->price * $i->quantity);

        if ($totalGross <= 0) {
            return 0;
        }

        $gross = $this->price * $this->quantity;
        $proportion = $gross / $totalGross;

        $saleLevelDiscount = 0;
        foreach ($sale->discounts as $d) {
            if (($d['type'] ?? '') === 'amount') {
                $saleLevelDiscount += $d['value'] ?? 0;
            } elseif (($d['type'] ?? '') === 'percent') {
                $saleLevelDiscount += (($d['value'] ?? 0) / 100) * $totalGross;
            }
        }

        return $saleLevelDiscount * $proportion;
    }
}
