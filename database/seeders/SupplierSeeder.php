<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Contact;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::factory()
            ->count(10) // 10 suppliers
            ->create()
            ->each(function ($supplier) {
                // Chaque supplier a entre 1 et 5 contacts
                Contact::factory()->count(rand(1, 5))->create([
                    'supplier_id' => $supplier->id,
                ]);
            });
    }
}
