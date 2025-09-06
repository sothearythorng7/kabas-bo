<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Store;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Store::create([
            'name'    => 'Phnom penh',
            'type'    => 'shop',
            'address' => '65 STREET 178',
            'phone'   => '111 222 333', 
            'email'   => 'phnompenh@kabasconceptstore.com',
        ]);
 
        Store::create([
            'name'    => 'Siem reap',
            'type'    => 'shop',
            'address' => '65 STREET 178',
            'phone'   => '111 222 333',
            'email'   => 'siemeap@kabasconceptstore.com',
        ]);

        Store::create([
            'name'    => 'Phnom penh - Warehouse',
            'type'    => 'warehouse',
            'address' => '65 STREET 178',
            'phone'   => '111 222 333',
            'email'   => 'siemeap_warehouse@kabasconceptstore.com',
        ]);
    }
}
