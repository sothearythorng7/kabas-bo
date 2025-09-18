<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'type', // 'buyer' ou 'consignment'
    ];

    protected $attributes = [
        'type' => 'buyer', // valeur par défaut
    ];

    /**
     * Contacts liés au fournisseur
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
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
}
