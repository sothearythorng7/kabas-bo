<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ResellerContact;
use App\Models\ResellerStockDelivery;
use App\Models\ResellerSalesReport;


class Reseller extends Model
{
    protected $fillable = ['name', 'type'];

    public function contacts() { return $this->hasMany(ResellerContact::class); }
    public function deliveries() { return $this->hasMany(ResellerStockDelivery::class); }
    public function reports() { return $this->hasMany(ResellerSalesReport::class); }
    public function stockBatches() { return $this->hasMany(ResellerStockBatch::class); }

    public function getCurrentStock()
    {
        return $this->stockBatches()
            ->select('product_id', \DB::raw('SUM(quantity) as total'))
            ->groupBy('product_id')
            ->pluck('total', 'product_id');
    }

    public function getStockValue()
    {
        return $this->stockBatches()
            ->join('products', 'reseller_stock_batches.product_id', '=', 'products.id')
            ->selectRaw('SUM(reseller_stock_batches.quantity * products.price) as total_value')
            ->value('total_value') ?? 0;
    }
}
