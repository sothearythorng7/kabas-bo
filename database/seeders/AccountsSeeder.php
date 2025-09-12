<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code'=>'530','name'=>'Caisse','type'=>'asset'],
            ['code'=>'512','name'=>'Banque','type'=>'asset'],
            ['code'=>'707','name'=>'Ventes','type'=>'income'],
            ['code'=>'601','name'=>'Achats','type'=>'expense'],
            ['code'=>'622','name'=>'Charges externes','type'=>'expense'],
        ];

        foreach ($accounts as $a) {
            Account::firstOrCreate(['code'=>$a['code']], $a);
        }
    }
}
