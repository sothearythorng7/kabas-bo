<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'raw_material_id',
        'quantity',
        'is_optional',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'is_optional' => 'boolean',
    ];

    /**
     * Recette parente
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Matière première utilisée
     */
    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    /**
     * Quantité nécessaire pour produire X unités
     */
    public function quantityFor(int $units): float
    {
        return $this->quantity * $units;
    }
}
