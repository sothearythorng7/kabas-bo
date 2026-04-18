<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Supplier extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'notes',
        'type', // 'buyer' ou 'consignment'
        'is_raw_material_supplier',
        'is_active',
    ];

    protected $attributes = [
        'type' => 'buyer', // valeur par défaut
        'is_raw_material_supplier' => false,
        'is_active' => true,
    ];

    protected $casts = [
        'is_raw_material_supplier' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ============ Laravel Scout / Meilisearch ============

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'notes' => $this->notes,
            'type' => $this->type,
            'is_raw_material_supplier' => (bool) $this->is_raw_material_supplier,
            'is_active' => (bool) $this->is_active,
        ];
    }

    public function searchableAs()
    {
        return 'suppliers';
    }

    /**
     * Contacts liés au fournisseur
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function refills()
    {
        return $this->hasMany(Refill::class);
    }

    /**
     * Retours de produits vers ce fournisseur (consignment)
     */
    public function returns()
    {
        return $this->hasMany(SupplierReturn::class);
    }


    /**
     * Produits fournis par ce fournisseur
     */
    public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('purchase_price')
                    ->withTimestamps();
    }

    /**
     * Commandes passées auprès de ce fournisseur
     * (Workflow "buyer")
     */
    public function supplierOrders()
    {
        return $this->hasMany(SupplierOrder::class);
    }

    /**
     * Alias pour supplierOrders (utilisé dans les vues factory)
     */
    public function orders()
    {
        return $this->hasMany(SupplierOrder::class);
    }

    /**
     * Rapports de ventes pour les fournisseurs en consignation
     * (Workflow "consignment")
     */
    public function saleReports()
    {
        return $this->hasMany(SaleReport::class);
    }

    /**
     * Vérifie si le fournisseur est de type buyer
     */
    public function isBuyer(): bool
    {
        return $this->type === 'buyer';
    }

    /**
     * Vérifie si le fournisseur est de type consignation
     */
    public function isConsignment(): bool
    {
        return $this->type === 'consignment';
    }

    /**
     * Vérifie si le fournisseur fournit des matières premières
     */
    public function isRawMaterialSupplier(): bool
    {
        return $this->is_raw_material_supplier;
    }

    /**
     * Matières premières fournies par ce fournisseur
     */
    public function rawMaterials()
    {
        return $this->belongsToMany(RawMaterial::class, 'raw_material_supplier')
            ->withPivot('purchase_price')
            ->withTimestamps();
    }

    /**
     * Scope pour les fournisseurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les fournisseurs de matières premières
     */
    public function scopeRawMaterialSuppliers($query)
    {
        return $query->where('is_raw_material_supplier', true);
    }

    /**
     * Scope pour les fournisseurs de produits (non matières premières)
     */
    public function scopeProductSuppliers($query)
    {
        return $query->where('is_raw_material_supplier', false);
    }
}
