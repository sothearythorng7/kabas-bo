<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Store;
use App\Models\Product;
use App\Models\ResellerSalesReport;
use App\Models\ResellerSalesReportItem;
use App\Models\ResellerSalesReportAnomaly;
use App\Models\ResellerInvoice;
use App\Models\ResellerProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;
use App\Models\FinancialPaymentMethod;
use Carbon\Carbon;

class ResellerSalesReportController extends Controller
{
    /**
     * Résout un reseller ou un shop depuis l'identifiant de route
     */
    protected function resolveResellerOrShop($resellerId)
    {
        if (str_starts_with($resellerId, 'shop-')) {
            $shopId = (int) str_replace('shop-', '', $resellerId);
            $shop = Store::findOrFail($shopId);
            $shop->is_shop = true;
            return $shop;
        }

        return Reseller::with('contacts')->findOrFail($resellerId);
    }

    /**
     * Affiche le formulaire de création de sales report
     */
    public function create($resellerId)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $isShop = ($reseller instanceof Store) || ($reseller->is_shop ?? false);

        if (!$isShop && ($reseller->type ?? null) !== 'consignment') {
            abort(403, 'Only consignment resellers can create sales reports.');
        }

        // Wrapper pour la vue afin de garder la compatibilité avec shop-<id>
        $resellerObj = $isShop
            ? (object)[
                'id' => 'shop-' . $reseller->id,
                'name' => $reseller->name,
                'type' => 'shop',
                'is_shop' => true,
                'store' => $reseller,
            ]
            : $reseller;

        $stock = $reseller->getCurrentStock();
        // All products ever delivered to this reseller (including those with 0 stock remaining)
        $allProductIds = $reseller->stockBatches()->distinct()->pluck('product_id');
        $products = Product::whereIn('id', $allProductIds)->orderBy('name')->get();

        return view('resellers.reports.create', compact('resellerObj', 'products', 'stock'))
            ->with('reseller', $resellerObj);
    }

    /**
     * Stocke un nouveau sales report
     */
    public function store(Request $request, $resellerId)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $isShop = ($reseller instanceof Store) || ($reseller->is_shop ?? false);

        if (!$isShop && ($reseller->type ?? null) !== 'consignment') {
            abort(403, 'Only consignment resellers or internal shops can create sales reports.');
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($reseller, $isShop, $validated, &$report, &$totalValue) {
            // Création du report attaché soit à reseller_id, soit store_id
            $report = ResellerSalesReport::create([
                'reseller_id' => $isShop ? null : $reseller->id,
                'store_id' => $isShop ? $reseller->id : null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            // Get current stock (stock at end of period = now)
            $currentStock = $reseller->getCurrentStock();

            // Get refills (deliveries received during period)
            $refillQuantities = $this->getRefillQuantities($reseller, $isShop, $startDate, $endDate);

            $productsData = Product::whereIn('id', collect($validated['products'])->pluck('id'))->get()->keyBy('id');
            $totalValue = 0;

            foreach ($validated['products'] as $p) {
                $product = $productsData[$p['id']];
                $quantitySold = $p['quantity'];

                // Stock on Hand = current stock (at end of period, before this sale deduction)
                $stockOnHand = ($currentStock[$product->id] ?? 0);

                // Refill = deliveries received during period
                $refill = $refillQuantities[$product->id] ?? 0;

                // Utiliser le prix B2B pour les revendeurs en consignement
                $unitPrice = $isShop
                    ? ($product->price_btob ?? $product->price)
                    : ResellerProductPrice::getPriceFor($reseller->id, $product->id);

                // Products with 0 sales: record in report but skip stock deduction
                if ($quantitySold <= 0) {
                    $oldStock = max(0, $stockOnHand - $refill);

                    ResellerSalesReportItem::create([
                        'report_id' => $report->id,
                        'product_id' => $product->id,
                        'old_stock' => $oldStock,
                        'refill' => $refill,
                        'stock_on_hand' => $stockOnHand,
                        'quantity_sold' => 0,
                        'unit_price' => $unitPrice,
                    ]);
                    continue;
                }

                // Calculate available stock for capping
                $availableStock = $reseller->stockBatches()
                    ->where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                // Cap stock deduction to available stock and create dispute if needed
                $deductQuantity = min($quantitySold, $availableStock);
                if ($quantitySold > $availableStock) {
                    ResellerSalesReportAnomaly::create([
                        'report_id' => $report->id,
                        'product_id' => $product->id,
                        'quantity' => $quantitySold - $deductQuantity,
                        'reported_quantity' => $quantitySold,
                        'accepted_quantity' => $deductQuantity,
                        'description' => $deductQuantity <= 0
                            ? 'Reported quantity exceeds available stock (no stock available)'
                            : 'Reported quantity exceeds available stock',
                        'status' => 'pending',
                    ]);
                }

                // Old Stock = Stock on Hand + Reported Quantity Sold - Refill
                // (Stock at start of period = Stock at end + what was sold - what was received)
                $oldStock = max(0, $stockOnHand + $quantitySold - $refill);

                // Invoice and report item use the REPORTED quantity (what was actually sold)
                ResellerSalesReportItem::create([
                    'report_id' => $report->id,
                    'product_id' => $product->id,
                    'old_stock' => $oldStock,
                    'refill' => $refill,
                    'stock_on_hand' => $stockOnHand,
                    'quantity_sold' => $quantitySold,
                    'unit_price' => $unitPrice,
                ]);

                $totalValue += $quantitySold * $unitPrice;

                // FIFO stock deduction (capped at available stock)
                $remaining = $deductQuantity;
                $batches = $reseller->stockBatches()
                    ->where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->quantity, $remaining);
                    $batch->quantity -= $deduct;
                    $batch->save();
                    $remaining -= $deduct;
                }
            }

            // Création de la facture
            $invoiceSearch = [
                'reseller_stock_delivery_id' => null,
                'sales_report_id' => $report->id,
                'reseller_id' => $isShop ? null : $reseller->id,
                'store_id' => $isShop ? $reseller->id : null,
            ];

            $invoice = ResellerInvoice::firstOrCreate(
                $invoiceSearch,
                [
                    'total_amount' => $totalValue,
                    'status' => 'unpaid',
                    'file_path' => null,
                ]
            );

            // Génération PDF
            $pdf = Pdf::loadView('resellers.reports.invoice', [
                'reseller' => $reseller,
                'report' => $report,
                'totalValue' => $totalValue,
            ])->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            $fileName = "sales_reports/sales_report_{$report->id}.pdf";
            Storage::put($fileName, $pdf->output());
            $invoice->update(['file_path' => $fileName, 'total_amount' => $totalValue]);
        });

        return redirect()->route('resellers.show', $resellerId)
            ->with('success', 'Sales report recorded, stock updated, and invoice generated.');
    }

    /**
     * Vérifie si le rapport peut être modifié/supprimé (non payé)
     */
    protected function ensureReportIsEditable(ResellerSalesReport $report): void
    {
        $invoice = $report->invoice;
        if ($invoice && $invoice->payments()->sum('amount') > 0) {
            abort(403, 'This report has payments and cannot be modified.');
        }
    }

    /**
     * Restaure le stock FIFO pour tous les items d'un rapport
     */
    protected function restoreStock($reseller, ResellerSalesReport $report): void
    {
        // Load anomalies to know how much was actually deducted vs reported
        $anomalies = ResellerSalesReportAnomaly::where('report_id', $report->id)
            ->get()
            ->keyBy('product_id');

        foreach ($report->items as $item) {
            if ($item->quantity_sold <= 0) continue;

            // Quantity actually deducted = reported - disputed portion
            $anomaly = $anomalies->get($item->product_id);
            $deducted = $anomaly
                ? ($anomaly->accepted_quantity ?? $item->quantity_sold)
                : $item->quantity_sold;

            if ($deducted <= 0) continue;

            // Re-add stock to the oldest batch with this product, or create one
            $batch = $reseller->stockBatches()
                ->where('product_id', $item->product_id)
                ->orderBy('created_at')
                ->first();

            if ($batch) {
                $batch->quantity += $deducted;
                $batch->save();
            } else {
                $reseller->stockBatches()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $deducted,
                ]);
            }
        }
    }

    /**
     * Formulaire d'édition d'un sales report (tant qu'il n'est pas payé)
     */
    public function edit($resellerId, ResellerSalesReport $report)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $this->ensureReportIsEditable($report);

        $report->load('items');

        $isShop = ($reseller instanceof Store) || ($reseller->is_shop ?? false);

        $resellerObj = $isShop
            ? (object)[
                'id' => 'shop-' . $reseller->id,
                'name' => $reseller->name,
                'type' => 'shop',
                'is_shop' => true,
                'store' => $reseller,
            ]
            : $reseller;

        // Stock actuel + quantités vendues dans ce rapport (pour afficher le stock "avant déduction")
        $currentStock = $reseller->getCurrentStock();
        $reportItems = $report->items->keyBy('product_id');

        // All products ever delivered
        $allProductIds = $reseller->stockBatches()->distinct()->pluck('product_id');
        $products = Product::whereIn('id', $allProductIds)->orderBy('name')->get();

        // Available stock = current stock + what was sold in this report (since we'll reverse it)
        $stock = [];
        foreach ($products as $product) {
            $sold = $reportItems[$product->id]->quantity_sold ?? 0;
            $stock[$product->id] = ($currentStock[$product->id] ?? 0) + $sold;
        }

        return view('resellers.reports.edit', compact('resellerObj', 'products', 'stock', 'report', 'reportItems'))
            ->with('reseller', $resellerObj);
    }

    /**
     * Met à jour un sales report (tant qu'il n'est pas payé)
     */
    public function update(Request $request, $resellerId, ResellerSalesReport $report)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $this->ensureReportIsEditable($report);
        $report->load('items');

        $isShop = ($reseller instanceof Store) || ($reseller->is_shop ?? false);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($reseller, $isShop, $validated, $report, &$totalValue) {
            // 1. Restore stock from old report
            $this->restoreStock($reseller, $report);

            // 2. Delete old items and anomalies
            $report->items()->delete();
            ResellerSalesReportAnomaly::where('report_id', $report->id)->delete();

            // 3. Update report dates
            $report->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            // 4. Re-create items (same logic as store)
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            $currentStock = $reseller->getCurrentStock();
            $refillQuantities = $this->getRefillQuantities($reseller, $isShop, $startDate, $endDate);
            $productsData = Product::whereIn('id', collect($validated['products'])->pluck('id'))->get()->keyBy('id');
            $totalValue = 0;

            foreach ($validated['products'] as $p) {
                $product = $productsData[$p['id']];
                $quantitySold = $p['quantity'];
                $stockOnHand = ($currentStock[$product->id] ?? 0);
                $refill = $refillQuantities[$product->id] ?? 0;

                $unitPrice = $isShop
                    ? ($product->price_btob ?? $product->price)
                    : ResellerProductPrice::getPriceFor($reseller->id, $product->id);

                if ($quantitySold <= 0) {
                    $oldStock = max(0, $stockOnHand - $refill);
                    ResellerSalesReportItem::create([
                        'report_id' => $report->id,
                        'product_id' => $product->id,
                        'old_stock' => $oldStock,
                        'refill' => $refill,
                        'stock_on_hand' => $stockOnHand,
                        'quantity_sold' => 0,
                        'unit_price' => $unitPrice,
                    ]);
                    continue;
                }

                $availableStock = $reseller->stockBatches()
                    ->where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                $deductQuantity = min($quantitySold, $availableStock);
                if ($quantitySold > $availableStock) {
                    ResellerSalesReportAnomaly::create([
                        'report_id' => $report->id,
                        'product_id' => $product->id,
                        'quantity' => $quantitySold - $deductQuantity,
                        'reported_quantity' => $quantitySold,
                        'accepted_quantity' => $deductQuantity,
                        'description' => $deductQuantity <= 0
                            ? 'Reported quantity exceeds available stock (no stock available)'
                            : 'Reported quantity exceeds available stock',
                        'status' => 'pending',
                    ]);
                }

                $oldStock = max(0, $stockOnHand + $quantitySold - $refill);

                ResellerSalesReportItem::create([
                    'report_id' => $report->id,
                    'product_id' => $product->id,
                    'old_stock' => $oldStock,
                    'refill' => $refill,
                    'stock_on_hand' => $stockOnHand,
                    'quantity_sold' => $quantitySold,
                    'unit_price' => $unitPrice,
                ]);

                $totalValue += $quantitySold * $unitPrice;

                // FIFO stock deduction (capped at available stock)
                $remaining = $deductQuantity;
                $batches = $reseller->stockBatches()
                    ->where('product_id', $product->id)
                    ->where('quantity', '>', 0)
                    ->orderBy('created_at')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $deduct = min($batch->quantity, $remaining);
                    $batch->quantity -= $deduct;
                    $batch->save();
                    $remaining -= $deduct;
                }
            }

            // 5. Update invoice
            if ($report->invoice) {
                $report->invoice->update(['total_amount' => $totalValue]);

                // Regenerate PDF
                $pdf = Pdf::loadView('resellers.reports.invoice', [
                    'reseller' => $reseller,
                    'report' => $report->fresh(['items.product']),
                    'totalValue' => $totalValue,
                ])->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ]);

                $fileName = "sales_reports/sales_report_{$report->id}.pdf";
                Storage::put($fileName, $pdf->output());
                $report->invoice->update(['file_path' => $fileName]);
            }
        });

        return redirect()->route('resellers.reports.show', [$resellerId, $report->id])
            ->with('success', __('messages.resellers.report_updated'));
    }

    /**
     * Supprime un sales report (tant qu'il n'est pas payé)
     */
    public function destroy($resellerId, ResellerSalesReport $report)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $this->ensureReportIsEditable($report);
        $report->load('items');

        DB::transaction(function () use ($reseller, $report) {
            // 1. Restore stock
            $this->restoreStock($reseller, $report);

            // 2. Delete PDF file
            if ($report->invoice && $report->invoice->file_path) {
                Storage::delete($report->invoice->file_path);
            }

            // 3. Delete invoice
            if ($report->invoice) {
                $report->invoice->delete();
            }

            // 4. Delete anomalies
            ResellerSalesReportAnomaly::where('report_id', $report->id)->delete();

            // 5. Delete items
            $report->items()->delete();

            // 6. Delete report
            $report->delete();
        });

        return redirect()->route('resellers.show', $resellerId)
            ->with('success', __('messages.resellers.report_deleted'));
    }

    /**
     * Affiche un sales report
     */
    public function show($resellerId, ResellerSalesReport $report)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $report->load('items.product', 'invoice.payments');

        $totalPaid = $report->invoice?->payments->sum('amount') ?? 0;
        $remaining = max(($report->invoice?->total_amount ?? 0) - $totalPaid, 0);

        $paymentStatus = !$report->invoice ? 'N/A'
            : ($remaining <= 0 ? 'paid'
            : ($totalPaid > 0 ? 'partially_paid' : 'unpaid'));

        $paymentsCount = $report->invoice?->payments->count() ?? 0;

        return view('resellers.reports.show', compact(
            'reseller', 'report', 'totalPaid', 'remaining', 'paymentStatus', 'paymentsCount'
        ));
    }

    /**
     * Téléchargement de la facture PDF
     */
    public function invoice($resellerId, ResellerSalesReport $report)
    {
        $reseller = $this->resolveResellerOrShop($resellerId);
        $isShop = ($reseller instanceof Store) || ($reseller->is_shop ?? false);

        $invoice = ResellerInvoice::where('sales_report_id', $report->id)
            ->where('reseller_stock_delivery_id', null)
            ->when($isShop, fn($q) => $q->where('store_id', $reseller->id), fn($q) => $q->where('reseller_id', $reseller->id))
            ->first();

        $totalValue = $report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price);

        if (!$invoice) {
            $invoice = ResellerInvoice::create([
                'reseller_id' => $isShop ? null : $reseller->id,
                'store_id' => $isShop ? $reseller->id : null,
                'reseller_stock_delivery_id' => null,
                'total_amount' => $totalValue,
                'status' => 'unpaid',
                'file_path' => null,
            ]);
        }

        $pdf = Pdf::loadView('resellers.reports.invoice', [
            'reseller' => $reseller,
            'report' => $report,
            'totalValue' => $totalValue
        ])->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $fileName = 'invoices/sales_report_' . $report->id . '.pdf';
        Storage::put($fileName, $pdf->output());
        $invoice->update(['file_path' => $fileName]);

        return $pdf->download('sales_report_' . $report->id . '.pdf');
    }

    /**
     * Ajout d'un paiement
     */
    /*
    public function addPayment(Request $request, $resellerId, ResellerSalesReport $report)
    {
        dd('ok');
        $reseller = $this->resolveResellerOrShop($resellerId);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'reference' => 'nullable|string|max:255',
        ]);

        $payment = $report->invoice->payments()->create([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => now(),
        ]);

        return redirect()->route('resellers.reports.show', [
            'reseller' => $resellerId,
            'report' => $report->id,
        ])->with('success', 'Paiement enregistré.');
    }
        */

public function addPayment(Request $request, $resellerId, ResellerSalesReport $report)
{
    $reseller = $this->resolveResellerOrShop($resellerId);
    $isShop = ($reseller instanceof \App\Models\Store) || ($reseller->is_shop ?? false);

    $data = $request->validate([
        'amount' => 'required|numeric|min:0.01',
        'payment_method' => 'required|in:cash,transfer',
        'reference' => 'nullable|string|max:255',
    ]);

    // Création du paiement
    $payment = $report->invoice->payments()->create([
        'amount' => $data['amount'],
        'payment_method' => $data['payment_method'],
        'reference' => $data['reference'] ?? null,
        'paid_at' => now(),
    ]);

    $wareHouse = Store::where('type', 'warehouse')->first();
    $paymentMethod = FinancialPaymentMethod::where('code', strtoupper($data['payment_method']))->first();
    $paymentMethodId = $paymentMethod ? $paymentMethod->id : 1;

    if ($wareHouse) {
        // --- Transaction crédit pour le warehouse ---
        $lastTransaction = FinancialTransaction::where('store_id', $wareHouse->id)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $balanceBefore = $lastTransaction?->balance_after ?? 0;
        $balanceAfter = $balanceBefore + $payment->amount;

        $url = route('resellers.reports.show', ['reseller' => $resellerId, 'report' => $report->id]);
        $path = ltrim(parse_url($url, PHP_URL_PATH), '/');

        FinancialTransaction::create([
            'store_id' => $wareHouse->id,
            'account_id' => FinancialAccount::where('code', '701')->first()->id,
            'amount' => $payment->amount,
            'currency' => 'USD',
            'direction' => 'credit',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'label' => "Paiement reçu de " . ($isShop ? "shop #{$reseller->id}" : "revendeur #{$reseller->id}"),
            'description' => "Paiement reçu pour sale report #{$report->invoice->id}",
            'status' => 'validated',
            'transaction_date' => now(),
            'payment_method_id' => $paymentMethodId,
            'user_id' => auth()->id(),
            'external_reference' => $path,
        ]);

        // --- Transaction débit pour le shop lui-même ---
        if ($isShop) {
            $lastTransactionShop = FinancialTransaction::where('store_id', $reseller->id)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $balanceBeforeShop = $lastTransactionShop?->balance_after ?? 0;
            $balanceAfterShop = $balanceBeforeShop - $payment->amount;

            FinancialTransaction::create([
                'store_id' => $reseller->id,
                'account_id' => FinancialAccount::where('code', '701')->first()->id,
                'amount' => $payment->amount,
                'currency' => 'USD',
                'direction' => 'debit',
                'balance_before' => $balanceBeforeShop,
                'balance_after' => $balanceAfterShop,
                'label' => "Paiement vers warehouse",
                'description' => "Paiement effectué pour sale report #{$report->invoice->id}",
                'status' => 'validated',
                'transaction_date' => now(),
                'payment_method_id' => $paymentMethodId,
                'user_id' => auth()->id(),
                'external_reference' => $path,
            ]);
        }
    }

    return redirect()->route('resellers.reports.show', [
        'reseller' => $resellerId, // <-- utiliser l'identifiant original
        'report' => $report->id,
    ])->with('success', 'Paiement enregistré et transaction générée.');
}

    /**
     * Resolve a dispute (anomaly)
     */
    public function resolveDispute(Request $request, $resellerId, ResellerSalesReportAnomaly $anomaly)
    {
        $request->validate([
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $anomaly->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'resolution_note' => $request->resolution_note,
        ]);

        return redirect()->back()->with('success', __('messages.resellers.dispute_resolved'));
    }

    /**
     * Get refill quantities (deliveries received) for a reseller during a period
     */
    private function getRefillQuantities($reseller, bool $isShop, Carbon $startDate, Carbon $endDate): array
    {
        $query = \App\Models\ResellerStockDelivery::query()
            ->where('status', 'shipped')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('delivered_at', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->whereNull('delivered_at')
                         ->whereBetween('created_at', [$startDate, $endDate]);
                  });
            });

        if ($isShop) {
            $query->where('store_id', $reseller->id);
        } else {
            $query->where('reseller_id', $reseller->id);
        }

        $deliveries = $query->with('products')->get();

        $refillQuantities = [];
        foreach ($deliveries as $delivery) {
            foreach ($delivery->products as $product) {
                $productId = $product->id;
                $refillQuantities[$productId] = ($refillQuantities[$productId] ?? 0) + $product->pivot->quantity;
            }
        }

        return $refillQuantities;
    }
}
