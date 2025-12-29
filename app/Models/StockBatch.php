<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id', 'store_id', 'reseller_id', 'quantity', 'unit_price', 'source_delivery_id', 'source_supplier_order_id', 'source_refill_id', 'source_production_id', 'source_exchange_id'
    ];

    public function refill() { return $this->belongsTo(Refill::class, 'source_refill_id'); }
    public function production() { return $this->belongsTo(Production::class, 'source_production_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function reseller() { return $this->belongsTo(Reseller::class); }
    public function delivery() { return $this->belongsTo(ResellerStockDelivery::class, 'source_delivery_id'); }
    public function exchange() { return $this->belongsTo(Exchange::class, 'source_exchange_id'); }
}
