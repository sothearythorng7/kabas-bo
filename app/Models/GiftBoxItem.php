<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftBoxItem extends Model
{
    protected $fillable = [
        'gift_box_id',
        'product_id',
        'quantity',
    ];

    public function giftBox()
    {
        return $this->belongsTo(GiftBox::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
