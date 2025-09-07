<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierOrder extends Model
{
    protected $fillable = ['supplier_id', 'status', 'destination_store_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_order_product')
            ->withPivot('purchase_price','sale_price','quantity_ordered','quantity_received')
            ->withTimestamps();
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }
}
