<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\FinancialAccountType;

return new class extends Migration {
    public function up(): void
    {
        DB::table('financial_accounts')->updateOrInsert(
            ['code' => '609'],
            [
                'name' => 'Charges promotionnelles',
                'type' => FinancialAccountType::EXPENSE->value,
                'parent_id' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $account = DB::table('financial_accounts')->where('code', '609')->first();
        if (! $account) {
            return;
        }

        $hasTransactions = DB::table('financial_transactions')
            ->where('account_id', $account->id)
            ->exists();

        if (! $hasTransactions) {
            DB::table('financial_accounts')->where('code', '609')->delete();
        }
    }
};
