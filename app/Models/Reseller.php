<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ResellerContact;
use App\Models\ResellerStockDelivery;
use App\Models\ResellerSalesReport;
use App\Models\ResellerProductPrice;
use App\Models\Store;


class Reseller extends Model
{
    protected $fillable = ['name', 'type'];

    public function contacts() { return $this->hasMany(ResellerContact::class); }
    public function deliveries() { return $this->hasMany(ResellerStockDelivery::class); }
    public function reports() { return $this->hasMany(ResellerSalesReport::class); }
    public function productPrices() { return $this->hasMany(ResellerProductPrice::class); }

    /**
     * Get products with custom B2B prices for this reseller.
     */
    public function productsWithPrices()
    {
        return $this->belongsToMany(Product::class, 'reseller_product_prices')
            ->withPivot('price')
            ->withTimestamps();
    }

    /**
     * Get the B2B price for a specific product for this reseller.
     */
    public function getPriceForProduct(int $productId): ?float
    {
        return ResellerProductPrice::getPriceFor($this->id, $productId);
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function getCurrentStock()
    {
        return $this->stockBatches()->select('product_id', \DB::raw('SUM(quantity) as total'))
                                    ->groupBy('product_id')
                                    ->pluck('total','product_id');
    }

    public function getStockValue()
    {
        return $this->stockBatches()
            ->join('products', 'reseller_stock_batches.product_id', '=', 'products.id')
            ->selectRaw('SUM(reseller_stock_batches.quantity * products.price) as total_value')
            ->value('total_value') ?? 0;
    }


    public static function allWithShops()
    {
        // On récupère les resellers classiques
        $resellers = self::with('contacts')->get();

        // On récupère les shops marqués comme resellers
        $shops = Store::where('is_reseller', true)->get()->map(function($shop) {
            return (object)[
                'id' => 'shop-'.$shop->id,
                'name' => $shop->name,
                'type' => 'consignment',
                'contacts' => collect(),
                'is_shop' => true,
                'store' => $shop,
            ];
        });

        return $resellers->concat($shops);
    }
}
