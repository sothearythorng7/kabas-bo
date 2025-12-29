<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FinancialTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $accounts = FinancialAccount::all();
        $methods = FinancialPaymentMethod::all();
        $user = User::first(); // ou tu peux randomiser un utilisateur

        foreach ($stores as $store) {
            $balance = 0;

            for ($i = 0; $i < 50; $i++) {
                $account = $accounts->random();
                $method = $methods->random();

                $amount = mt_rand(50, 1000); // montant aléatoire entre 50 et 1000
                $direction = ['debit', 'credit'][array_rand(['debit', 'credit'])];

                $balanceBefore = $balance;
                $balanceAfter = $direction === 'debit' ? $balance - $amount : $balance + $amount;
                $balance = $balanceAfter;

                $transactionDate = Carbon::now()->subDays(mt_rand(0, 90)); // date aléatoire dans les 3 derniers mois

                FinancialTransaction::create([
                    'store_id' => $store->id,
                    'account_id' => $account->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'direction' => $direction,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'label' => 'Transaction ' . Str::random(5),
                    'description' => 'Description aléatoire',
                    'status' => 'validated',
                    'transaction_date' => $transactionDate,
                    'payment_method_id' => $method->id,
                    'user_id' => $user?->id,
                ]);
            }
        }
    }
}
