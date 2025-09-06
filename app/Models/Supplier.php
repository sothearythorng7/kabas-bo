<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address'];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function products() {
        return $this->belongsToMany(Product::class)
            ->withPivot('purchase_price')
            ->withTimestamps();
    }

    public function supplierOrders()
    {
        return $this->hasMany(SupplierOrder::class);
    }
}