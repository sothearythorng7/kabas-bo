<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = ['shipping_country_id', 'shipping_carrier_id', 'weight_from', 'weight_to', 'price'];

    protected function casts(): array
    {
        return [
            'weight_from' => 'decimal:2',
            'weight_to' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function country()
    {
        return $this->belongsTo(ShippingCountry::class, 'shipping_country_id');
    }

    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }
}
