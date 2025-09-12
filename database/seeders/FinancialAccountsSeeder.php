<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\FinancialAccountType;

class FinancialAccountsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('financial_accounts')->insertOrIgnore([
            // Asset
            ['code' => '101', 'name' => 'Capital social', 'type' => FinancialAccountType::ASSET->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '512', 'name' => 'Banque', 'type' => FinancialAccountType::ASSET->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '530', 'name' => 'Caisse', 'type' => FinancialAccountType::ASSET->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // Liability
            ['code' => '401', 'name' => 'Fournisseurs', 'type' => FinancialAccountType::LIABILITY->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '411', 'name' => 'Clients', 'type' => FinancialAccountType::LIABILITY->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // Expense
            ['code' => '606', 'name' => 'Achats', 'type' => FinancialAccountType::EXPENSE->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '613', 'name' => 'Locations', 'type' => FinancialAccountType::EXPENSE->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '622', 'name' => 'Honoraires', 'type' => FinancialAccountType::EXPENSE->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],

            // Revenue
            ['code' => '701', 'name' => 'Ventes de marchandises', 'type' => FinancialAccountType::REVENUE->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '707', 'name' => 'Prestations de services', 'type' => FinancialAccountType::REVENUE->value, 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
