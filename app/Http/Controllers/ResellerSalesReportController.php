<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\Store;
use App\Models\Product;
use App\Models\ResellerSalesReport;
use App\Models\ResellerSalesReportItem;
use App\Models\ResellerSalesReportAnomaly;
use App\Models\ResellerInvoice;
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

        return Reseller::findOrFail($resellerId);
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
        $products = Product::whereIn('id', $stock->keys())->get();

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
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($reseller, $isShop, $validated, &$report, &$totalValue) {
            // Création du report attaché soit à reseller_id, soit store_id
            $report = ResellerSalesReport::create([
                'reseller_id' => $isShop ? null : $reseller->id,
                'store_id' => $isShop ? $reseller->id : null,
            ]);

            $productsData = Product::whereIn('id', collect($validated['products'])->pluck('id'))->get()->keyBy('id');
            $totalValue = 0;

            foreach ($validated['products'] as $p) {
                if ($p['quantity'] <= 0) continue;

                $product = $productsData[$p['id']];

                ResellerSalesReportItem::create([
                    'report_id' => $report->id,
                    'product_id' => $product->id,
                    'quantity_sold' => $p['quantity'],
                    'unit_price' => $product->price,
                ]);

                $totalValue += $p['quantity'] * $product->price;

                // Déduction FIFO dans le stock
                $remaining = $p['quantity'];
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

                if ($remaining > 0) {
                    ResellerSalesReportAnomaly::create([
                        'report_id' => $report->id,
                        'product_id' => $product->id,
                        'quantity' => $remaining,
                        'description' => 'Reported quantity exceeds available stock',
                    ]);
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
            'currency' => 'EUR',
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
                'currency' => 'EUR',
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


}
