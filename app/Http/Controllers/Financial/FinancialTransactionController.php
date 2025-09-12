<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use App\Models\Store;
use Illuminate\Http\Request;

class FinancialTransactionController extends Controller
{
    public function index(Store $store)
    {
        $transactions = FinancialTransaction::where('store_id', $store->id)
            ->with(['account', 'paymentMethod'])
            ->latest()
            ->paginate(20);

        return view('financial.transactions.index', compact('transactions', 'store'));
    }

    public function create(Store $store)
    {
        $accounts = FinancialAccount::all();
        $methods = FinancialPaymentMethod::all();
        return view('financial.transactions.create', compact('store', 'accounts', 'methods'));
    }

    public function store(Request $request, Store $store)
    {
        $request->validate([
            'account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric',
            'direction' => 'required|in:debit,credit',
            'transaction_date' => 'required|date',
            'payment_method_id' => 'required|exists:financial_payment_methods,id',
        ]);

        $last = FinancialTransaction::where('store_id', $store->id)->latest('transaction_date')->first();
        $balanceBefore = $last?->balance_after ?? 0;
        $balanceAfter = $request->direction === 'debit'
            ? $balanceBefore - $request->amount
            : $balanceBefore + $request->amount;

        FinancialTransaction::create([
            'store_id' => $store->id,
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'currency' => 'EUR',
            'direction' => $request->direction,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => $request->label ?? 'Transaction',
            'description' => $request->description,
            'status' => 'validated',
            'transaction_date' => $request->transaction_date,
            'payment_method_id' => $request->payment_method_id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('financial.transactions.index', $store->id)
            ->with('success', 'Transaction ajoutée');
    }
}
