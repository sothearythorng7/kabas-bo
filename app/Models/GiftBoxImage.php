<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftBoxImage extends Model
{
    protected $fillable = [
        'gift_box_id',
        'path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function giftBox()
    {
        return $this->belongsTo(GiftBox::class);
    }
}
