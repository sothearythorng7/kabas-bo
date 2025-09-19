<?php

namespace App\Http\Controllers;

use App\Models\SaleReport;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\Product;
use App\Models\FinancialPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SaleReportController extends Controller
{
    public function index(Supplier $supplier)
    {
        $reportsByStatus = [
            'waiting_invoice' => $supplier->saleReports()->where('status', 'waiting_invoice')->with('store')->paginate(10, ['*'], 'waiting_invoice'),
            'invoiced_unpaid' => $supplier->saleReports()->where('status', 'invoiced')->where('is_paid', false)->with('store')->paginate(10, ['*'], 'invoiced_unpaid'),
            'invoiced_paid' => $supplier->saleReports()->where('status', 'invoiced')->where('is_paid', true)->with('store')->paginate(10, ['*'], 'invoiced_paid'),
        ];

        $totalTheoretical = $supplier->saleReports()->sum('total_amount_theoretical');
        $totalUnpaidInvoiced = $supplier->saleReports()
            ->where('status', 'invoiced')
            ->where('is_paid', false)
            ->sum('total_amount_invoiced');

        $paymentMethods = FinancialPaymentMethod::all();

        return view('sale_reports.overview', compact(
            'supplier',
            'reportsByStatus',
            'totalTheoretical',
            'totalUnpaidInvoiced',
            'paymentMethods'
        ));
    }



    public function create(Supplier $supplier)
    {
        $stores = Store::all();
        $products = $supplier->products()->get();

        return view('sale_reports.create', compact('supplier', 'stores', 'products'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'report_date' => 'required|date',
            'products' => 'required|array',
            'products.*.quantity_sold' => 'required|integer|min:0',
        ]);

        $lastReport = SaleReport::where('supplier_id', $supplier->id)
            ->where('store_id', $request->store_id)
            ->orderByDesc('period_end')
            ->first();

        $period_start = $lastReport ? $lastReport->period_end : $request->report_date;
        $period_end = $request->report_date;

        $total = 0;
        $items = [];

        foreach ($request->products as $productId => $data) {
            $quantity = $data['quantity_sold'] ?? 0;
            if ($quantity > 0) {
                $product = $supplier->products()->where('products.id', $productId)->first();
                $unitPrice = $product->pivot->purchase_price ?? 0;

                $items[] = [
                    'product_id' => $productId,
                    'quantity_sold' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $unitPrice * $quantity,
                ];

                $total += $unitPrice * $quantity;
            }
        }

        if (empty($items)) {
            return back()
                ->withInput()
                ->withErrors(['products' => 'Vous devez saisir au moins une quantité vendue.']);
        }

        $saleReport = $supplier->saleReports()->create([
            'store_id' => $request->store_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'status' => 'waiting_invoice',
            'is_paid' => false,
            'total_amount_theoretical' => $total,
        ]);

        foreach ($items as $item) {
            $saleReport->items()->create($item);
        }

        return redirect(route('sale-reports.index', $supplier))
            ->with('success', 'Sale report created.');
    }

    public function invoiceReception(Supplier $supplier, SaleReport $saleReport)
    {
        $saleReport->load('items.product');
        
        return view('sale_reports.reception_invoice', compact('supplier', 'saleReport'));
    }

    public function storeInvoiceReception(Request $request, Supplier $supplier, SaleReport $saleReport)
    {
        $request->validate([
            'invoice_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'products' => 'required|array',
            'products.*.price_invoiced' => 'required|numeric|min:0',
        ]);

        $totalInvoiced = 0;

        foreach ($saleReport->items as $item) {
            $priceInvoiced = $request->products[$item->product_id]['price_invoiced'] ?? $item->unit_price;
            $item->update(['price_invoiced' => $priceInvoiced]);
            $totalInvoiced += $priceInvoiced * $item->quantity_sold;

            // maj prix référence si demandé
            if (isset($request->update_reference_price[$item->product_id])) {
                $item->product->suppliers()->updateExistingPivot($supplier->id, [
                    'purchase_price' => $priceInvoiced,
                ]);
            }
        }

        // upload facture
        $path = $request->file('invoice_file')->store('invoices/sale_reports', 'public');

        $saleReport->update([
            'status' => 'invoiced',
            'is_paid' => false,
            'invoice_file_path' => $path,
            'total_amount_invoiced' => $totalInvoiced,
        ]);

        return redirect(route('suppliers.edit', $supplier) . '#sales-reports')
            ->with('success', 'Facture réceptionnée avec succès.');
    }

    public function markAsPaid(Supplier $supplier, SaleReport $saleReport)
    {
        if (!in_array($saleReport->status, ['invoiced', 'waiting_payment'])) {
            return back()->with('error', 'Le sale report doit être invoiced ou waiting_payment pour être payé.');
        }

        // Récupération du store associé
        $storeId = $saleReport->store_id;

        // Montant facturé = somme des prix facturés (ou prix théorique si non renseigné)
        $amount = $saleReport->items->sum(fn($item) => $item->price_invoiced ?? $item->unit_price ?? 0 * $item->quantity_sold);

        // Compte fournisseur (401)
        $account = \App\Models\FinancialAccount::where('code', '401')->firstOrFail();

        // Solde précédent
        $lastTransaction = \App\Models\FinancialTransaction::where('store_id', $storeId)
            ->latest('transaction_date')
            ->first();
        $balanceBefore = $lastTransaction?->balance_after ?? 0;
        $balanceAfter = $balanceBefore - $amount;

        // Création de la transaction
        $transaction = \App\Models\FinancialTransaction::create([
            'store_id' => $storeId,
            'account_id' => $account->id,
            'amount' => $amount,
            'currency' => 'EUR',
            'direction' => 'debit', // sortie d'argent pour le store
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => 'Paiement sale report : ' . $supplier->name,
            'description' => "Paiement du sale report #{$saleReport->id} pour {$supplier->name}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => 1, // méthode par défaut, à adapter si nécessaire
            'user_id' => auth()->id(),
            'external_reference' => route('sale-reports.show', [$supplier, $saleReport]),
        ]);

        // Ajouter la facture comme pièce jointe si elle existe
        if ($saleReport->invoice_file) {
            $transaction->attachments()->create([
                'path' => $saleReport->invoice_file,
                'file_type' => \Illuminate\Support\Facades\Storage::mimeType($saleReport->invoice_file),
                'uploaded_by' => auth()->id(),
            ]);
        }

        // Mettre à jour le statut du sale report
        $saleReport->update(['is_paid' => true]);
        return redirect()->back()->with('success', 'Sale report marqué comme payé et transaction créée.');
    }


    public function show(Supplier $supplier, SaleReport $saleReport)
    {
        $saleReport->load('items.product');
        return view('sale_reports.show', compact('supplier', 'saleReport'));
    }
}
