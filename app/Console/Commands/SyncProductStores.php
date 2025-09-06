<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Store;

class SyncProductStores extends Command
{
    protected $signature = 'products:sync-stores';
    protected $description = 'Ensure all products are linked to all stores with stock_quantity = 0 if missing';

    public function handle()
    {
        $this->info('Fetching all products and stores...');
        $products = Product::all();
        $stores = Store::all();

        if($products->isEmpty() || $stores->isEmpty()) {
            $this->warn('No products or stores found.');
            return 0;
        }

        $this->info("Syncing stores for {$products->count()} products...");

        foreach ($products as $product) {
            $syncData = [];

            foreach ($stores as $store) {
                // Si le produit n'a pas encore ce store
                if (!$product->stores->contains($store->id)) {
                    $syncData[$store->id] = ['stock_quantity' => 0];
                }
            }

            if ($syncData) {
                $product->stores()->attach($syncData);
                $this->info("Product ID {$product->id}: attached " . count($syncData) . " missing stores.");
            }
        }

        $this->info('Sync completed.');
        return 0;
    }
}
