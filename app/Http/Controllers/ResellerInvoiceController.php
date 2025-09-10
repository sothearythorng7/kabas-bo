<?php

namespace App\Http\Controllers;

use App\Models\ResellerInvoice;
use App\Models\ResellerStockDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $invoice->payments()->create([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
        ]);

        return redirect()->route('reseller-invoices.show', $invoice)
            ->with('success', 'Paiement enregistré.');
    }

}
