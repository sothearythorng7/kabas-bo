<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupEventItem extends Model
{
    protected $fillable = [
        'popup_event_id', 'product_id', 'quantity_allocated', 'quantity_sold',
    ];

    protected $casts = [
        'quantity_allocated' => 'integer',
        'quantity_sold' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(PopupEvent::class, 'popup_event_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getQuantityRemainingAttribute()
    {
        return $this->quantity_allocated - $this->quantity_sold;
    }

    public function getSellThroughRateAttribute()
    {
        if ($this->quantity_allocated <= 0) return 0;
        return round(($this->quantity_sold / $this->quantity_allocated) * 100, 1);
    }
}
