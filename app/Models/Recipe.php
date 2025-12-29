<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'product_id',
        'instructions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Produit fini créé par cette recette
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Ingrédients (items) de la recette
     */
    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * Matières premières (via recipe_items)
     */
    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'recipe_items')
            ->withPivot('quantity', 'is_optional', 'notes')
            ->withTimestamps();
    }

    /**
     * Productions utilisant cette recette
     */
    public function productions()
    {
        return $this->hasMany(Production::class);
    }

    /**
     * Scope pour les recettes actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Vérifie si toutes les matières premières sont disponibles pour produire X unités
     */
    public function canProduce(int $quantity = 1): bool
    {
        foreach ($this->items as $item) {
            if ($item->is_optional) continue;

            $rawMaterial = $item->rawMaterial;
            if (!$rawMaterial->track_stock) continue;

            $requiredQty = $item->quantity * $quantity;
            if ($rawMaterial->total_stock < $requiredQty) {
                return false;
            }
        }
        return true;
    }

    /**
     * Quantité maximum productible avec le stock actuel
     */
    public function maxProducible(): int
    {
        $max = PHP_INT_MAX;

        foreach ($this->items as $item) {
            if ($item->is_optional) continue;

            $rawMaterial = $item->rawMaterial;
            if (!$rawMaterial->track_stock) continue;

            if ($item->quantity > 0) {
                $possible = floor($rawMaterial->total_stock / $item->quantity);
                $max = min($max, $possible);
            }
        }

        return $max === PHP_INT_MAX ? 0 : (int) $max;
    }
}
