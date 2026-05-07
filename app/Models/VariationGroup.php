<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariationGroup extends Model
{
    protected $fillable = ['name', 'description', 'display_product_id'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'variation_group_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariationAttribute::class, 'variation_group_id');
    }

    public function displayProduct()
    {
        return $this->belongsTo(Product::class, 'display_product_id');
    }

    /**
     * Nom générique traduit, avec fallback sur le nom d'un produit du groupe si pas défini.
     */
    public function genericName(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $name = $this->name ?? [];

        $value = $name[$loc] ?? $name['en'] ?? $name['fr'] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        // Fallback : utiliser le nom du produit de référence ou du premier produit du groupe
        $ref = $this->resolveDisplayProduct();
        if ($ref) {
            $pn = $ref->name ?? [];
            return $pn[$loc] ?? $pn['en'] ?? $pn['fr'] ?? (is_array($pn) && !empty($pn) ? reset($pn) : '') ?? '';
        }

        return '';
    }

    /**
     * Description générique traduite (nullable).
     */
    public function genericDescription(?string $locale = null): ?string
    {
        $loc = $locale ?: app()->getLocale();
        $desc = $this->description ?? [];

        $value = $desc[$loc] ?? $desc['en'] ?? $desc['fr'] ?? null;
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * Retourne le produit de référence : `display_product_id` s'il est défini,
     * sinon le produit du groupe avec l'ID le plus bas (comportement legacy).
     */
    public function resolveDisplayProduct(): ?Product
    {
        if ($this->display_product_id) {
            $dp = $this->displayProduct;
            if ($dp) {
                return $dp;
            }
        }
        return $this->products()->orderBy('id')->first();
    }
}
