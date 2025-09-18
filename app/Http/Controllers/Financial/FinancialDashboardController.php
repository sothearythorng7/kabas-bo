<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\GeneralInvoice;

class FinancialDashboardController extends Controller
{

public function index(Store $store, Request $request)
{
    // --- SOLDE ACTUEL ---
    $lastTransaction = FinancialTransaction::where('store_id', $store->id)
        ->orderBy('transaction_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();
    $currentBalance = $lastTransaction?->balance_after ?? 0;

    // --- ENTRÉES ET SORTIES DU MOIS ---
    $monthTransactions = FinancialTransaction::where('store_id', $store->id)
        ->whereMonth('transaction_date', now()->month)
        ->get();

    $monthCredits = $monthTransactions->where('direction', 'credit')->sum('amount');
    $monthDebits = $monthTransactions->where('direction', 'debit')->sum('amount');

    // --- TOP COMPTES ---
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

    // --- RÉPARTITION PAR MÉTHODE DE PAIEMENT ---
    $paymentDistribution = FinancialTransaction::where('store_id', $store->id)
        ->selectRaw('payment_method_id, SUM(amount) as total')
        ->groupBy('payment_method_id')
        ->with('paymentMethod')
        ->get()
        ->mapWithKeys(fn($t) => [$t->paymentMethod->name => $t->total]);

    // --- DONNÉES DU GRAPHIQUE ---
    $period = $request->get('period', 'month'); // 'month', '6months', 'all'
    $startDate = match($period) {
        'month' => Carbon::now()->startOfMonth(),
        '6months' => Carbon::now()->subMonths(6)->startOfMonth(),
        default => FinancialTransaction::where('store_id', $store->id)
                    ->orderBy('transaction_date')
                    ->first()?->transaction_date ?? Carbon::now(),
    };

    $transactions = FinancialTransaction::where('store_id', $store->id)
        ->where('transaction_date', '>=', $startDate)
        ->orderBy('transaction_date')
        ->get();

    $dates = [];
    $credits = [];
    $debits = [];
    $balancePerDay = [];

    $balance = 0;
    $transactionsGrouped = $transactions->groupBy(fn($t) => $t->transaction_date->format('Y-m-d'));

    foreach ($transactionsGrouped as $date => $dayTransactions) {
        $dayCredit = $dayTransactions->where('direction', 'credit')->sum('amount');
        $dayDebit = $dayTransactions->where('direction', 'debit')->sum('amount');

        // Recalcul du solde du jour
        foreach ($dayTransactions as $t) {
            $balance = $t->balance_after;
        }

        $dates[] = $date;
        $credits[] = $dayCredit;
        $debits[] = $dayDebit;
        $balancePerDay[] = $balance;
    }

    // --- ALERT FACTURES NON PAYÉES ---
    $unpaidGeneralInvoices = GeneralInvoice::where('store_id', $store->id)
        ->where('status', 'pending')
        ->get();
    $unpaidSupplierOrders = \App\Models\SupplierOrder::where('status', 'received')
        ->where('is_paid', false)
        ->get();

    $unpaidInvoicesCount = $unpaidGeneralInvoices->count() + $unpaidSupplierOrders->count();
    $unpaidInvoicesTotal = $unpaidGeneralInvoices->sum('amount') +
        $unpaidSupplierOrders->sum(fn($order) =>
            $order->products->sum(fn($p) =>
                ($p->pivot->price_invoiced ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0)
            )
        );

    return view('financial.dashboard', compact(
        'store',
        'currentBalance',
        'monthCredits',
        'monthDebits',
        'topAccounts',
        'paymentDistribution',
        'dates',
        'credits',
        'debits',
        'balancePerDay',
        'period',
        'unpaidInvoicesCount',
        'unpaidInvoicesTotal'
    ));
}

public function overviewInvoices(Request $request)
{
    // On récupère les commandes fournisseurs reçues mais non payées
    $ordersQuery = \App\Models\SupplierOrder::with(['supplier', 'destinationStore', 'products'])
        ->where('status', 'received')
        ->where('is_paid', false)
        ->latest();

    // Pagination
    $orders = $ordersQuery->paginate(15)->appends($request->query());

    // Montant total des factures à payer
    $totalUnpaidAmount = $ordersQuery->get()->sum(fn($order) =>
        $order->products->sum(fn($p) =>
            ($p->pivot->price_invoiced ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_ordered ?? 0)
        )
    );

    return view('financial.overview_invoices', compact('orders', 'totalUnpaidAmount'));
}
}
