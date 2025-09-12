<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use Illuminate\Http\Request;

class FinancialDashboardController extends Controller
{
    public function index(Store $store)
    {
        // Solde actuel
        $lastTransaction = FinancialTransaction::where('store_id', $store->id)
            ->latest('transaction_date')
            ->first();
        $currentBalance = $lastTransaction?->balance_after ?? 0;

        // Entrées et sorties du mois
        $monthTransactions = FinancialTransaction::where('store_id', $store->id)
            ->whereMonth('transaction_date', now()->month)
            ->get();

        $monthCredits = $monthTransactions->where('direction', 'credit')->sum('amount');
        $monthDebits = $monthTransactions->where('direction', 'debit')->sum('amount');

        // Top comptes
        $topAccounts = FinancialTransaction::where('store_id', $store->id)
            ->selectRaw('account_id, SUM(amount) as total')
            ->groupBy('account_id')
            ->with('account')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($t) => (object)[
                'name' => $t->account->name,
                'total' => $t->total
            ]);

        // Répartition par méthode de paiement
        $paymentDistribution = FinancialTransaction::where('store_id', $store->id)
            ->selectRaw('payment_method_id, SUM(amount) as total')
            ->groupBy('payment_method_id')
            ->with('paymentMethod')
            ->get()
            ->mapWithKeys(fn($t) => [$t->paymentMethod->name => $t->total]);

        return view('financial.dashboard', compact(
            'store', 'currentBalance', 'monthCredits', 'monthDebits', 'topAccounts', 'paymentDistribution'
        ));
    }
}
