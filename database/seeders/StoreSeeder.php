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
            'name'    => 'Kabas - Phnom penh',
            'type'    => 'shop',
            'is_reseller' => true,
            'address' => '65 STREET 178',
            'phone'   => '111 222 333', 
            'email'   => 'phnompenh@kabasconceptstore.com',
        ]);
 
        Store::create([
            'name'    => 'Kabas - Siem reap',
            'type'    => 'shop',
            'is_reseller' => true,
            'address' => '65 STREET 178',
            'phone'   => '111 222 333',
            'email'   => 'siemeap@kabasconceptstore.com',
        ]);

        Store::create([
            'name'    => 'Kabas - Warehouse',
            'type'    => 'warehouse',
            'is_reseller' => false,
            'address' => '65 STREET 178',
            'phone'   => '111 222 333',
            'email'   => 'siemeap_warehouse@kabasconceptstore.com',
        ]);
    }
}
