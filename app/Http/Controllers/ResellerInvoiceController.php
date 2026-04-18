<?php

namespace App\Http\Controllers;

use App\Models\ResellerInvoice;
use App\Models\ResellerStockDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use Carbon\Carbon;
use App\Models\Store;
use App\Models\FinancialPaymentMethod;

class ResellerInvoiceController extends Controller
{
    public function index()
    {
        $statuses = ['unpaid', 'partially_paid', 'paid'];

        $invoicesByStatus = [];
        foreach ($statuses as $status) {
            $invoicesByStatus[$status] = ResellerInvoice::with('reseller', 'salesReport', 'resellerStockDelivery', 'payments')
                ->where('status', $status)
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        // Montant total des factures en attente (unpaid + partially_paid)
        $pendingInvoices = ResellerInvoice::with('payments')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->get();

        $totalPending = $pendingInvoices->sum(function ($invoice) {
            // Montant restant à payer
            $paid = $invoice->payments->sum('amount');
            return $invoice->total_amount - $paid;
        });

        return view('reseller_invoices.index', compact('statuses', 'invoicesByStatus', 'totalPending'));
    }


    public function show(ResellerInvoice $invoice)
    {
        // Charger les relations nécessaires
        $invoice->load([
            'reseller',
            'resellerStockDelivery.products',
            'salesReport.items.product',
            'payments'
        ]);

        return view('reseller_invoices.show', compact('invoice'));
    }

    public function markAsPaid(Request $request, ResellerInvoice $invoice)
    {
        $data = $request->validate([
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:financial_payment_methods,id',
            'payment_reference' => 'nullable|string|max:255',
            'payment_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $paid = $invoice->payments->sum('amount');
        $remaining = $invoice->total_amount - $paid;

        if ($remaining <= 0) {
            return redirect()->back()->with('error', 'Invoice already fully paid.');
        }

        // Store proof file if provided
        $proofPath = null;
        if ($request->hasFile('payment_proof')) {
            $proofPath = $request->file('payment_proof')->store('reseller_payment_proofs', 'public');
        }

        // Create payment for the remaining amount
        $payment = $invoice->payments()->create([
            'amount' => $remaining,
            'payment_method' => FinancialPaymentMethod::find($data['payment_method_id'])->code ?? 'transfer',
            'reference' => $data['payment_reference'],
            'paid_at' => $data['payment_date'],
            'proof_path' => $proofPath,
        ]);

        // Update invoice status
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $data['payment_date'],
        ]);

        // Financial transaction
        $wareHouse = Store::where('type', 'warehouse')->first();
        $entity = $invoice->reseller ?? $invoice->store;
        $isShop = !$invoice->reseller_id;

        if ($wareHouse) {
            $account = FinancialAccount::where('code', '701')->first();
            $paymentMethodId = $data['payment_method_id'];

            $lastTransaction = FinancialTransaction::where('store_id', $wareHouse->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $balanceBefore = $lastTransaction?->balance_after ?? 0;
            $balanceAfter = $balanceBefore + $remaining;

            $url = route('reseller-invoices.show', $invoice);
            $path = ltrim(parse_url($url, PHP_URL_PATH), '/');

            FinancialTransaction::create([
                'store_id' => $wareHouse->id,
                'account_id' => $account->id,
                'amount' => $remaining,
                'currency' => 'USD',
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'label' => "Paiement reçu de " . ($entity->name ?? 'revendeur'),
                'description' => "Paiement complet pour facture #{$invoice->id}",
                'status' => 'validated',
                'transaction_date' => Carbon::parse($data['payment_date']),
                'payment_method_id' => $paymentMethodId,
                'user_id' => auth()->id(),
                'external_reference' => $path,
            ]);

            // Debit on shop side if it's a shop
            if ($isShop && $invoice->store_id) {
                $lastTransactionShop = FinancialTransaction::where('store_id', $invoice->store_id)
                    ->orderBy('transaction_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                $balanceBeforeShop = $lastTransactionShop?->balance_after ?? 0;
                $balanceAfterShop = $balanceBeforeShop - $remaining;

                FinancialTransaction::create([
                    'store_id' => $invoice->store_id,
                    'account_id' => $account->id,
                    'amount' => $remaining,
                    'currency' => 'USD',
                    'direction' => 'debit',
                    'balance_before' => $balanceBeforeShop,
                    'balance_after' => $balanceAfterShop,
                    'label' => "Paiement vers warehouse",
                    'description' => "Paiement effectué pour facture #{$invoice->id}",
                    'status' => 'validated',
                    'transaction_date' => Carbon::parse($data['payment_date']),
                    'payment_method_id' => $paymentMethodId,
                    'user_id' => auth()->id(),
                    'external_reference' => $path,
                ]);
            }
        }

        return redirect()->back()->with('success', __('messages.reseller_invoice.payment_recorded'));
    }

    public function addPayment(Request $request, ResellerInvoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'reference' => 'nullable|string|max:255',
        ]);

        // Création du paiement
        $payment = $invoice->payments()->create([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
        ]);

        $wareHouse = Store::where('type', 'warehouse')->first();
        $reseller = $invoice->reseller;

        // Créer la transaction financière pour tous les types de revendeurs (buyer et consignment)
        if ($reseller && $wareHouse) {

            $account = FinancialAccount::where('code', '701')->first();
            if (!$account) {
                throw new \Exception("Le compte caisse (701) est introuvable.");
            }

            // Récupérer la dernière transaction pour calcul du solde
            $lastTransaction = FinancialTransaction::where('store_id', $wareHouse->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $balanceBefore = $lastTransaction?->balance_after ?? 0;
            $balanceAfter = $balanceBefore + $payment->amount;

            // Référence pour aller directement sur la facture ou la commande
            if ($invoice->reseller_stock_delivery_id) {
                $url = route('reseller-stock-deliveries.edit', [
                    'reseller' => $reseller->id,
                    'delivery' => $invoice->reseller_stock_delivery_id,
                ]);
            } else {
                $url = route('reseller-invoices.show', $invoice);
            }
            $path = ltrim(parse_url($url, PHP_URL_PATH), '/');

            $paymentMethod = FinancialPaymentMethod::where('code', strtoupper($data['payment_method']))->first();
            $paymentMethodId = $paymentMethod ? $paymentMethod->id : 1;

            // Créer la transaction crédit
            FinancialTransaction::create([
                'store_id' => $wareHouse->id,
                'account_id' => $account->id,
                'amount' => $payment->amount,
                'currency' => 'USD',
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'label' => "Paiement revendeur {$reseller->name}",
                'description' => "Paiement reçu pour facture #{$invoice->id}",
                'status' => 'validated',
                'transaction_date' => Carbon::now(),
                'payment_method_id' => $paymentMethodId,
                'user_id' => auth()->id(),
                'external_reference' => $path
            ]);
        }

        return redirect()->route('reseller-invoices.show', $invoice)
            ->with('success', __('messages.reseller_invoice.payment_recorded'));
    }

}
