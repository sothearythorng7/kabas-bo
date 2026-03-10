<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCountry extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'continent', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByContinent($query, string $continent)
    {
        return $query->where('continent', $continent);
    }

    public static function continents(): array
    {
        return ['Africa', 'Asia', 'Europe', 'North America', 'South America', 'Oceania'];
    }
}
