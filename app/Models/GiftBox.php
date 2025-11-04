<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class GiftBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'ean',
        'name',
        'description',
        'slugs',
        'price',
        'price_btob',
        'brand_id',
        'is_active',
        'is_best_seller',
        'attributes',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'slugs' => 'array',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'is_best_seller' => 'boolean',
    ];

    // Relations
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(GiftBoxImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(GiftBoxImage::class)->where('is_primary', true);
    }

    public function items()
    {
        return $this->hasMany(GiftBoxItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'gift_box_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // Génération automatique des slugs
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($giftBox) {
            if ($giftBox->name) {
                $slugs = [];
                foreach ($giftBox->name as $locale => $name) {
                    $slugs[$locale] = Str::slug($name);
                }
                $giftBox->slugs = $slugs;
            }
        });
    }

    // URL publique du coffret
    public function publicUrl($locale = 'fr')
    {
        $slug = $this->slugs[$locale] ?? $this->slugs['fr'] ?? 'gift-box';
        return url("/{$locale}/gift-box/{$slug}");
    }
}
