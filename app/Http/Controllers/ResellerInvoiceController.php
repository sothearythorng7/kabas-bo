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
            $invoicesByStatus[$status] = ResellerInvoice::with('reseller', 'resellerStockDelivery', 'payments')
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

        // Vérifier si le revendeur est un "buyer"
        $reseller = $invoice->reseller;
        if ($reseller && $reseller->type === 'buyer' && $wareHouse) {
  
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

            //Référence pour aller directement sur la commande
            $url = route('reseller-stock-deliveries.edit', [
                'reseller' => $reseller->id,
                'delivery' => $invoice->reseller_stock_delivery_id,
            ]);
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');

           $paymentMethod = FinancialPaymentMethod::where('code', strtoupper($data['payment_method']))->first();
           $paymentMethodId = $paymentMethod ? $paymentMethod->id : 1;

            // Créer la transaction crédit
            FinancialTransaction::create([
                'store_id' => $wareHouse->id,
                'account_id' => $account->id,
                'amount' => $payment->amount,
                'currency' => 'EUR',
                'direction' => 'credit',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'label' => "Paiement revendeur #{$reseller->id}",
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
