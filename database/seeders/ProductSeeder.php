<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\StockBatch;
use App\Models\StockTransaction;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Brand::all();
        $stores = Store::all();
        $suppliers = Supplier::all();

        if ($brands->isEmpty() || $stores->isEmpty() || $suppliers->isEmpty()) {
            $this->command->error('Brands, Stores or Suppliers missing. Seed them first.');
            return;
        }

        $locales = config('app.website_locales', ['en', 'fr']);

        for ($i = 1; $i <= 20; $i++) { // 20 produits pour l'exemple
            $names = [];
            $descriptions = [];
            $slugs = [];

            foreach ($locales as $locale) {
                $names[$locale] = $locale === 'en' ? "Product $i" : "Produit $i";
                $descriptions[$locale] = $locale === 'en'
                    ? "Description of product $i in English."
                    : "Description du produit $i en français.";
                $slugs[$locale] = Str::slug($names[$locale]);
            }

            $product = Product::create([
                'ean' => 'EAN' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'name' => $names,
                'description' => $descriptions,
                'price' => rand(50, 500),
                'brand_id' => $brands->random()->id,
                'color' => fake()->safeColorName(),
                'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
                'is_active' => true,
                'is_best_seller' => (bool) rand(0, 1),
                'slugs' => $slugs,
                'is_resalable' => $i <= 10 ? true : false
            ]);

            // Associer des suppliers aléatoires
            $suppliers->random(rand(1, 3))->each(function ($supplier) use ($product) {
                $product->suppliers()->attach($supplier->id, [
                    'purchase_price' => rand(20, 200),
                ]);
            });

            $categories = Category::all();
            if ($categories->isNotEmpty()) {
                $randomCategories = $categories->random(rand(1, min(3, $categories->count())));
                $product->categories()->attach($randomCategories->pluck('id')->toArray());
            }

            // Créer des lots / batchs pour chaque magasin ou reseller
            $stores->each(function ($store) use ($product, $suppliers) {
                if ($store->is_reseller) {
                    $numLots = rand(1, 3);
                    for ($j = 0; $j < $numLots; $j++) {
                        $supplier = $suppliers->random();
                        $qty = 50;

                        $batch = StockBatch::create([
                            'product_id' => $product->id,
                            'store_id' => $store->id,
                            'quantity' => $qty,
                            'unit_price' => rand(20, 200),
                            'source_delivery_id' => null,
                        ]);

                        StockTransaction::create([
                            'stock_batch_id' => $batch->id,
                            'store_id'       => $store->id,
                            'product_id'     => $product->id,
                            'type'           => 'in',
                            'quantity'       => $qty,
                            'reason'         => 'seeding',
                        ]);
                    }
                }
            });
        }
    }
}
