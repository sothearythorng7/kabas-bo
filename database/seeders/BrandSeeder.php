<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        // Crée 50 marques aléatoires
        Brand::factory()->count(50)->create();
    }
}
