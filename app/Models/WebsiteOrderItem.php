<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteOrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'item_type',
        'gift_box_id',
        'gift_card_id',
        'source_promotion_id',
        'source_item_id',
        'product_name',
        'product_sku',
        'product_image',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:5',
            'subtotal' => 'decimal:5',
            'quantity' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(WebsiteOrder::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function giftBox()
    {
        return $this->belongsTo(GiftBox::class);
    }

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function sourcePromotion()
    {
        return $this->belongsTo(PromotionRule::class, 'source_promotion_id');
    }

    public function sourceItem()
    {
        return $this->belongsTo(self::class, 'source_item_id');
    }
}
