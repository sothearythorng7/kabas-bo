<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierOrderInvoiceLine extends Model
{
    protected $fillable = [
        'supplier_order_id',
        'product_id',
        'reference_price',
        'invoiced_price',
        'update_reference',
    ];

    public function order()
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
