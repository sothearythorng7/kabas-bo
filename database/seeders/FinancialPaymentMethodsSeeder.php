<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinancialPaymentMethodsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('financial_payment_methods')->insertOrIgnore([
            ['name' => 'Cash', 'code' => 'CASH', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Virement', 'code' => 'WIRE', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Voucher', 'code' => 'VOUCHER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
