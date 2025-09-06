<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Brand;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::all();

        if ($brands->isEmpty()) {
            $this->command->error('No brands found. Please seed brands first.');
            return;
        }

        $locales = config('app.website_locales', ['en', 'fr']);

        for ($i = 1; $i <= 10; $i++) {
            $names = [];
            $descriptions = [];
            $slugs = [];

            foreach ($locales as $locale) {
                $names[$locale] = $locale === 'en' ? "Product $i" : "Produit $i";
                $descriptions[$locale] = $locale === 'en'
                    ? "This is the description of product $i in English."
                    : "Ceci est la description du produit $i en français.";
                $slugs[$locale] = Str::slug($names[$locale]);
            }

            Product::create([
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
            ]);
        }
    }
}
