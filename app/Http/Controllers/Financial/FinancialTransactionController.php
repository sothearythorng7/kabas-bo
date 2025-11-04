<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use App\Models\FinancialJournal;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exports\FinancialTransactionsExport;
use Maatwebsite\Excel\Facades\Excel;

class FinancialTransactionController extends Controller
{
    public function index(Store $store, Request $request)
    {
        $query = FinancialTransaction::where('store_id', $store->id)
            ->with(['account', 'paymentMethod', 'user']);

        // Filtre par date
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Filtre par comptes (array d'IDs)
        if ($request->filled('account_ids')) {
            $query->whereIn('account_id', $request->account_ids);
        }

        // Filtre par montant
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Filtre par méthode de paiement (array d'IDs)
        if ($request->filled('payment_method_ids')) {
            $query->whereIn('payment_method_id', $request->payment_method_ids);
        }

        $transactions = $query->latest()->paginate(20)->appends($request->all());

        // Pour les filtres dropdown
        $accounts = FinancialAccount::all();
        $methods = FinancialPaymentMethod::all();

        return view('financial.transactions.index', compact('store', 'transactions', 'accounts', 'methods'));
    }

    public function export(Store $store, Request $request)
    {
        $query = FinancialTransaction::where('store_id', $store->id)
            ->with(['account', 'paymentMethod', 'user']);
        $filters = $request->all();
        // Appliquer les mêmes filtres que pour l'affichage
        if ($request->filled('date_from')) $query->whereDate('transaction_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('transaction_date', '<=', $request->date_to);
        if ($request->filled('account_ids')) $query->whereIn('account_id', $request->account_ids);
        if ($request->filled('amount_min')) $query->where('amount', '>=', $request->amount_min);
        if ($request->filled('amount_max')) $query->where('amount', '<=', $request->amount_max);
        if ($request->filled('payment_method_ids')) $query->whereIn('payment_method_id', $request->payment_method_ids);

        $transactions = $query->latest()->get();

        return Excel::download(
            new FinancialTransactionsExport($store->id, $filters),
            'transactions.xlsx'
        );
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
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,png,docx',
        ]);

        // Calcul du solde
        $last = FinancialTransaction::where('store_id', $store->id)->latest('transaction_date')->first();
        $balanceBefore = $last?->balance_after ?? 0;
        $balanceAfter = $request->direction === 'debit'
            ? $balanceBefore - $request->amount
            : $balanceBefore + $request->amount;

        // Création de la transaction
        $transaction = FinancialTransaction::create([
            'store_id' => $store->id,
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'EUR',
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

        // Gestion des pièces jointes
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('financial_transactions', 'public');
                $transaction->attachments()->create([
                    'path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        // Création du log de création
        $transaction->logs()->create([
            'action' => 'created',
            'old_values' => null,
            'new_values' => $transaction->toArray(),
            'performed_by' => auth()->id(),
        ]);

        // Création du journal comptable
        FinancialJournal::create([
            'store_id' => $store->id,
            'type' => $transaction->direction === 'debit' ? 'out' : 'in',
            'account_id' => $transaction->account_id,
            'amount' => $transaction->amount,
            'reference' => $transaction->label,
            'description' => $transaction->description,
            'date' => $transaction->transaction_date,
        ]);

        return redirect()->route('financial.transactions.index', $store->id)
            ->with('success', __('messages.financial_transaction.added'));
    }

    public function show(Store $store, FinancialTransaction $transaction)
    {
        return view('financial.transactions.show', compact('store', 'transaction'));
    }

    public function edit(Store $store, FinancialTransaction $transaction)
    {
        $accounts = FinancialAccount::all();
        $methods = FinancialPaymentMethod::all();
        return view('financial.transactions.edit', compact('store', 'transaction', 'accounts', 'methods'));
    }

    public function update(Request $request, Store $store, FinancialTransaction $transaction)
    {
        $request->validate([
            'account_id' => 'required|exists:financial_accounts,id',
            'amount' => 'required|numeric',
            'direction' => 'required|in:debit,credit',
            'transaction_date' => 'required|date',
            'payment_method_id' => 'required|exists:financial_payment_methods,id',
            'attachments.*' => 'file|max:10240|mimes:pdf,jpg,png,docx',
            'delete_attachments.*' => 'exists:financial_transaction_attachments,id',
        ]);

        // Récupération des anciennes valeurs pour le log
        $oldValues = $transaction->getOriginal();

        // Mise à jour de la transaction
        $transaction->update([
            'account_id' => $request->account_id,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'EUR',
            'direction' => $request->direction,
            'label' => $request->label,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'payment_method_id' => $request->payment_method_id,
        ]);

        // Gestion des nouvelles pièces jointes
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('financial_transactions', 'public');
                $transaction->attachments()->create([
                    'path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }

        // Suppression des pièces jointes
        if ($request->filled('delete_attachments')) {
            $deletedFiles = $transaction->attachments()->whereIn('id', $request->delete_attachments)->get();
            foreach ($deletedFiles as $file) {
                Storage::disk('public')->delete($file->path);
                $file->delete();
                $transaction->logs()->create([
                    'action' => 'attachment_deleted',
                    'old_values' => ['filename' => $file->filename, 'path' => $file->path],
                    'new_values' => null,
                    'performed_by' => auth()->id(),
                ]);
            }
        }

        // Log de modification
        $transaction->logs()->create([
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $transaction->toArray(),
            'performed_by' => auth()->id(),
        ]);

        // Mise à jour ou création du journal comptable
        $journal = FinancialJournal::where('store_id', $store->id)
            ->where('account_id', $transaction->account_id)
            ->whereDate('date', $transaction->transaction_date->format('Y-m-d'))
            ->first();

        if ($journal) {
            $journal->update([
                'type' => $transaction->direction === 'debit' ? 'out' : 'in',
                'amount' => $transaction->amount,
                'reference' => $transaction->label,
                'description' => $transaction->description,
            ]);
        } else {
            FinancialJournal::create([
                'store_id' => $store->id,
                'type' => $transaction->direction === 'debit' ? 'out' : 'in',
                'account_id' => $transaction->account_id,
                'amount' => $transaction->amount,
                'reference' => $transaction->label,
                'description' => $transaction->description,
                'date' => $transaction->transaction_date,
            ]);
        }

        return redirect()->route('financial.transactions.index', $store->id)
            ->with('success', __('messages.financial_transaction.updated'));
    }

    public function destroy(Store $store, FinancialTransaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('financial.transactions.index', $store->id)
            ->with('success', __('messages.financial_transaction.deleted'));
    }
}
