<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactorySupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Matières premières fournies par ce fournisseur
     */
    public function rawMaterials()
    {
        return $this->hasMany(RawMaterial::class);
    }

    /**
     * Scope pour les fournisseurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
