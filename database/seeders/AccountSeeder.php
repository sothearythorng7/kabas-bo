<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'code' => '101',
                'name' => 'Cash',
                'description' => 'Compte caisse pour les liquidités',
                'type' => 'asset',
            ],
            [
                'code' => '201',
                'name' => 'Accounts Payable',
                'description' => 'Dette envers les fournisseurs',
                'type' => 'liability',
            ],
            [
                'code' => '301',
                'name' => 'Equity Capital',
                'description' => 'Capital social de l’entreprise',
                'type' => 'equity',
            ],
            [
                'code' => '401',
                'name' => 'Revenue',
                'description' => 'Revenus générés par l’entreprise',
                'type' => 'income',
            ],
            [
                'code' => '501',
                'name' => 'Expenses',
                'description' => 'Dépenses et charges diverses',
                'type' => 'expense',
            ],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}
