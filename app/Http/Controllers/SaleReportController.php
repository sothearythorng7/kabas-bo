<?php

namespace App\Http\Controllers;

use App\Models\SaleReport;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\FinancialPaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendSaleReportEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\SendTelegramReport;
use Illuminate\Support\Str;


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


    /**
     * Étape 1 : Sélection du magasin et des dates
     */
    public function create(Supplier $supplier)
    {
        $stores = Store::all();

        // Get last report dates per store for default period_start
        $lastReportsByStore = SaleReport::where('supplier_id', $supplier->id)
            ->orderByDesc('period_end')
            ->get()
            ->groupBy('store_id')
            ->map(fn($reports) => $reports->first()->period_end->format('Y-m-d'));

        return view('sale_reports.create_step1', compact('supplier', 'stores', 'lastReportsByStore'));
    }

    /**
     * Étape 2 : Affichage des produits avec quantités pré-remplies depuis le POS
     */
    public function createStep2(Request $request, Supplier $supplier)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $store = Store::findOrFail($request->store_id);
        $period_start = $request->period_start;
        $period_end = $request->period_end;

        // Récupérer les produits du fournisseur
        $products = $supplier->products()->get();
        $productIds = $products->pluck('id')->toArray();

        // Récupérer les ventes POS pour ce magasin et cette période
        $posSalesQuantities = $this->getPosSalesQuantities($store->id, $period_start, $period_end, $productIds);

        // Récupérer les refills pendant la période
        $refillQuantities = $this->getRefillQuantities($supplier->id, $store->id, $period_start, $period_end);

        // Récupérer les retours fournisseur pendant la période
        $returnQuantities = $this->getReturnQuantities($supplier->id, $store->id, $period_start, $period_end);

        // Récupérer le stock actuel par produit
        $currentStockQuantities = $this->getCurrentStockQuantities($store->id, $productIds);

        // Calculer le stock initial (old_stock = stock_actuel + ventes + retours - refills)
        $oldStockQuantities = [];
        foreach ($productIds as $productId) {
            $currentStock = $currentStockQuantities[$productId] ?? 0;
            $sales = $posSalesQuantities[$productId] ?? 0;
            $refills = $refillQuantities[$productId] ?? 0;
            $returns = $returnQuantities[$productId] ?? 0;
            $oldStockQuantities[$productId] = max(0, $currentStock + $sales + $returns - $refills);
        }

        $hasPosSales = array_sum($posSalesQuantities) > 0;

        return view('sale_reports.create', compact(
            'supplier',
            'store',
            'period_start',
            'period_end',
            'products',
            'posSalesQuantities',
            'refillQuantities',
            'returnQuantities',
            'currentStockQuantities',
            'oldStockQuantities',
            'hasPosSales'
        ));
    }

    /**
     * Récupère les quantités vendues depuis le POS pour une période et un magasin donnés
     * (ventes brutes moins les retours/échanges)
     */
    private function getPosSalesQuantities(int $storeId, string $periodStart, string $periodEnd, array $productIds): array
    {
        $quantities = [];

        // Récupérer les ventes du POS pour la période et le magasin
        $salesItems = SaleItem::whereHas('sale', function ($query) use ($storeId, $periodStart, $periodEnd) {
            $query->where('store_id', $storeId)
                ->whereDate('created_at', '>=', $periodStart)
                ->whereDate('created_at', '<=', $periodEnd);
        })
        ->whereIn('product_id', $productIds)
        ->selectRaw('product_id, SUM(quantity) as total_quantity')
        ->groupBy('product_id')
        ->get();

        foreach ($salesItems as $item) {
            $quantities[$item->product_id] = (int) $item->total_quantity;
        }

        // Déduire les quantités retournées via les échanges
        $returnedQuantities = $this->getExchangeReturnQuantities($storeId, $periodStart, $periodEnd, $productIds);
        foreach ($returnedQuantities as $productId => $returnedQty) {
            if (isset($quantities[$productId])) {
                $quantities[$productId] = max(0, $quantities[$productId] - $returnedQty);
            }
        }

        return $quantities;
    }

    /**
     * Récupère les quantités retournées via les échanges pour une période et un magasin donnés
     */
    private function getExchangeReturnQuantities(int $storeId, string $periodStart, string $periodEnd, array $productIds): array
    {
        $quantities = [];

        $exchangeItems = \App\Models\ExchangeItem::whereHas('exchange', function ($query) use ($storeId, $periodStart, $periodEnd) {
            $query->where('store_id', $storeId)
                ->whereDate('created_at', '>=', $periodStart)
                ->whereDate('created_at', '<=', $periodEnd);
        })
        ->whereIn('product_id', $productIds)
        ->selectRaw('product_id, SUM(quantity) as total_quantity')
        ->groupBy('product_id')
        ->get();

        foreach ($exchangeItems as $item) {
            $quantities[$item->product_id] = (int) $item->total_quantity;
        }

        return $quantities;
    }

    /**
     * Récupère les quantités reçues (Refill + SupplierOrder) pour une période et un magasin donnés
     */
    private function getRefillQuantities(int $supplierId, int $storeId, string $periodStart, string $periodEnd): array
    {
        $quantities = [];

        // Refills
        $refills = \App\Models\Refill::where('supplier_id', $supplierId)
            ->where('destination_store_id', $storeId)
            ->whereDate('created_at', '>=', $periodStart)
            ->whereDate('created_at', '<=', $periodEnd)
            ->with('products')
            ->get();

        foreach ($refills as $refill) {
            foreach ($refill->products as $product) {
                $productId = $product->id;
                $qty = $product->pivot->quantity_received ?? 0;
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
            }
        }

        // Commandes fournisseur reçues
        $orders = \App\Models\SupplierOrder::where('supplier_id', $supplierId)
            ->where('destination_store_id', $storeId)
            ->where('status', 'received')
            ->whereDate('created_at', '>=', $periodStart)
            ->whereDate('created_at', '<=', $periodEnd)
            ->with('products')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $productId = $product->id;
                $qty = $product->pivot->quantity_received ?? 0;
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
            }
        }

        return $quantities;
    }

    /**
     * Récupère les quantités retournées au fournisseur pour une période et un magasin donnés
     */
    private function getReturnQuantities(int $supplierId, int $storeId, string $periodStart, string $periodEnd): array
    {
        $quantities = [];

        $returns = \App\Models\SupplierReturn::where('supplier_id', $supplierId)
            ->where('store_id', $storeId)
            ->whereIn('status', ['pending', 'validated'])
            ->whereDate('created_at', '>=', $periodStart)
            ->whereDate('created_at', '<=', $periodEnd)
            ->with('items')
            ->get();

        foreach ($returns as $return) {
            foreach ($return->items as $item) {
                $productId = $item->product_id;
                $qty = $item->quantity ?? 0;
                $quantities[$productId] = ($quantities[$productId] ?? 0) + $qty;
            }
        }

        return $quantities;
    }

    /**
     * Récupère le stock actuel par produit pour un magasin
     */
    private function getCurrentStockQuantities(int $storeId, array $productIds): array
    {
        $quantities = [];

        $stocks = \App\Models\StockBatch::whereIn('product_id', $productIds)
            ->where('store_id', $storeId)
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->groupBy('product_id')
            ->get();

        foreach ($stocks as $stock) {
            $quantities[$stock->product_id] = (int) $stock->total_quantity;
        }

        return $quantities;
    }

    public function store(Request $request, Supplier $supplier)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'period_start' => 'required|date',
            'report_date' => 'required|date|after_or_equal:period_start',
            'products' => 'required|array',
            'products.*.old_stock' => 'required|integer|min:0',
            'products.*.refill' => 'required|integer|min:0',
            'products.*.returns' => 'required|integer|min:0',
            'products.*.stock_on_hand' => 'required|integer|min:0',
            'products.*.quantity_sold' => 'nullable|integer|min:0',
        ]);

        $period_start = $request->period_start;
        $period_end = $request->report_date;

        $totalPayAmount = 0;
        $totalSaleAmount = 0;
        $items = [];

        foreach ($request->products as $productId => $data) {
            $oldStock = $data['old_stock'] ?? 0;
            $refill = $data['refill'] ?? 0;
            $returns = $data['returns'] ?? 0;
            $stockOnHand = $data['stock_on_hand'] ?? 0;
            // Utiliser quantity_sold du POS si fourni, sinon calculer
            $quantitySold = isset($data['quantity_sold']) && $data['quantity_sold'] > 0
                ? (int) $data['quantity_sold']
                : max(0, $oldStock + $refill - $returns - $stockOnHand);

            $product = $supplier->products()->where('products.id', $productId)->first();
            $unitPrice = $product->pivot->purchase_price ?? 0; // Cost price
            $sellingPrice = $product->price * $quantitySold; // Total selling price

            $items[] = [
                'product_id' => $productId,
                'old_stock' => $oldStock,
                'refill' => $refill,
                'returns' => $returns,
                'stock_on_hand' => $stockOnHand,
                'quantity_sold' => $quantitySold,
                'unit_price' => $unitPrice,
                'selling_price' => $sellingPrice,
                'total' => $unitPrice * $quantitySold,
            ];

            $totalPayAmount += $unitPrice * $quantitySold;
            $totalSaleAmount += $sellingPrice;
        }

        $store = Store::findOrFail($request->store_id);

        $saleReport = $supplier->saleReports()->create([
            'store_id' => $request->store_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'status' => 'waiting_invoice',
            'is_paid' => false,
            'total_amount_theoretical' => $totalPayAmount,
        ]);

        foreach ($items as $item) {
            $saleReport->items()->create($item);
        }

        // Génération du PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sale_reports.pdf', [
            'saleReport' => $saleReport->load('items.product', 'supplier', 'store')
        ])->setPaper('a4', 'landscape');

        $supplierName = Str::slug($supplier->name, '_');
        $storeName = Str::slug($store->name, '_');
        $dateStart = \Carbon\Carbon::parse($period_start)->format('dmY');
        $dateEnd = \Carbon\Carbon::parse($period_end)->format('dmY');
        $hash = substr(md5(now()->timestamp . $saleReport->id), 0, 8);
        $filename = strtoupper("{$supplierName}_{$storeName}_{$dateStart}_{$dateEnd}") . "_{$hash}.pdf";

        $path = "sale_reports/{$filename}";
        \Storage::disk('public')->put($path, $pdf->output());

        $saleReport->update([
            'report_file_path' => $path
        ]);

        return redirect(route('sale-reports.index', $supplier))
            ->with('success', 'Sale report created.');
    }


    public function sendReport(Supplier $supplier, SaleReport $saleReport)
    {
        $contacts = $supplier->contacts()->whereNotNull('email')->get();
        return view('sale_reports.send', compact('supplier', 'saleReport', 'contacts'));
    }

    public function doSendReport(Request $request, Supplier $supplier, SaleReport $saleReport)
    {
        $request->validate([
            'recipients' => 'required|array',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $emails = $request->recipients;
        $subject = $request->subject;
        $body = $request->body;

        // Dispatch du job
        SendSaleReportEmail::dispatch($emails, $saleReport, $subject, $body);

        return redirect()->route('sale-reports.show', [$supplier, $saleReport])
            ->with('success', 'Le rapport sera envoyé en arrière-plan.');
    }

    public function doSendReportTelegram(Request $request, Supplier $supplier, SaleReport $saleReport)
    {
        $request->validate([
            'recipients'   => 'required|array',
            'body'         => 'required|string',
            'report_file'  => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $recipients = $request->recipients;
        $message    = $request->body;

        // Déterminer le chemin du fichier PDF
        if ($request->hasFile('report_file')) {
            $file = $request->file('report_file')->store('temp', 'public');
            $file = storage_path('app/public/' . $file);
        } else {
            $file = $saleReport->report_file_path
                ? storage_path('app/public/' . $saleReport->report_file_path)
                : null;
        }

        // Dispatch du Job (pas d'Artisan ici !)
        SendTelegramReport::dispatch($recipients, $message, $file);

        return redirect()
            ->route('sale-reports.show', [$supplier, $saleReport])
            ->with('success', 'Le rapport sera envoyé par Telegram en arrière-plan.');
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
        $amount = $saleReport->items->sum(fn($item) => ($item->price_invoiced ?? $item->unit_price ?? 0) * $item->quantity_sold);

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
            'currency' => 'USD',
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

    public function destroy(Supplier $supplier, SaleReport $saleReport)
    {
        if ($saleReport->status !== 'waiting_invoice') {
            return back()->with('error', 'Only sale reports with status "waiting_invoice" can be deleted.');
        }

        // Delete PDF file if exists
        if ($saleReport->report_file_path) {
            Storage::disk('public')->delete($saleReport->report_file_path);
        }

        $saleReport->items()->delete();
        $saleReport->delete();

        return redirect(route('suppliers.edit', $supplier) . '#sales-reports')
            ->with('success', 'Sale report deleted successfully.');
    }

    public function regeneratePdf(Supplier $supplier, SaleReport $saleReport)
    {
        // Delete old PDF
        if ($saleReport->report_file_path) {
            \Storage::disk('public')->delete($saleReport->report_file_path);
        }

        $saleReport->load('items.product', 'supplier', 'store');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sale_reports.pdf', [
            'saleReport' => $saleReport
        ])->setPaper('a4', 'landscape');

        $supplierName = Str::slug($saleReport->supplier->name, '_');
        $storeName = Str::slug($saleReport->store->name, '_');
        $dateStart = $saleReport->period_start->format('dmY');
        $dateEnd = $saleReport->period_end->format('dmY');
        $hash = substr(md5(now()->timestamp . $saleReport->id), 0, 8);
        $filename = strtoupper("{$supplierName}_{$storeName}_{$dateStart}_{$dateEnd}") . "_{$hash}.pdf";

        $path = "sale_reports/{$filename}";
        \Storage::disk('public')->put($path, $pdf->output());

        $saleReport->update(['report_file_path' => $path]);

        return redirect()->route('sale-reports.show', [$supplier, $saleReport])
            ->with('success', 'PDF regenerated successfully.');
    }
}
