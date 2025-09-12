<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerSalesReport;
use App\Models\ResellerSalesReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ResellerInvoice;

class ResellerSalesReportController extends Controller
{
    public function create(Reseller $reseller)
    {
        if ($reseller->type !== 'consignment') {
            abort(403, 'Only consignment resellers can create sales reports.');
        }

        $stock = $reseller->getCurrentStock();
        $products = \App\Models\Product::whereIn('id', $stock->keys())->get();

        return view('resellers.reports.create', compact('reseller', 'products', 'stock'));
    }

    public function store(Request $request, Reseller $reseller)
    {
        if ($reseller->type !== 'consignment' && !($reseller->is_shop ?? false)) {
            abort(403, 'Only consignment resellers or internal shops can create sales reports.');
        }

        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($reseller, $validated) {

            // --- Création du rapport ---
            $report = ResellerSalesReport::create([
                'reseller_id' => $reseller->id
            ]);

            $productsData = \App\Models\Product::whereIn('id', collect($validated['products'])->pluck('id'))->get()->keyBy('id');

            $totalValue = 0;

            foreach ($validated['products'] as $p) {
                if ($p['quantity'] > 0) {
                    $product = $productsData[$p['id']];

                    // Crée l'item du rapport
                    ResellerSalesReportItem::create([
                        'report_id'     => $report->id,
                        'product_id'    => $product->id,
                        'quantity_sold' => $p['quantity'],
                        'unit_price'    => $product->price,
                    ]);

                    $totalValue += $p['quantity'] * $product->price;

                    // Déduction du stock (FIFO)
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
                        \App\Models\ResellerSalesReportAnomaly::create([
                            'report_id'   => $report->id,
                            'product_id'  => $product->id,
                            'quantity'    => $remaining,
                            'description' => "Reported quantity exceeds available stock",
                        ]);
                    }
                }
            }

            // --- Génération de la facture ---
            $invoice = \App\Models\ResellerInvoice::firstOrCreate(
                [
                    'reseller_id' => $reseller->id,
                    'reseller_stock_delivery_id' => null,
                    'sales_report_id' => $report->id,
                ],
                [
                    'total_amount' => $totalValue,
                    'status' => 'unpaid',
                    'file_path' => null,
                ]
            );

            $pdf = Pdf::loadView('resellers.reports.invoice', [
                'reseller'   => $reseller,
                'report'     => $report,
                'totalValue' => $totalValue,
            ])->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            $fileName = "sales_reports/sales_report_{$report->id}.pdf";
            Storage::put($fileName, $pdf->output());

            $invoice->update(['file_path' => $fileName, 'total_amount' => $totalValue]);

            // --- Comptabilité dynamique ---
            $isShop = $reseller->is_shop ?? false;

            if ($isShop) {
                $shop = $reseller->store;

                // Récupération dynamique des comptes
                $warehouse = Store::where('type', 'warehouse')->firstOrFail();

                $shopSupplierAccount = $shop->accounts()->where('type', 'supplier')->firstOrFail();
                $warehouseReceivableAccount = $warehouse->accounts()->where('type', 'receivable')->firstOrFail();

                // 1️⃣ Comptabilité du shop : facture fournisseur
                $shop->journals()->create([
                    'date' => now(),
                    'account_id' => $shopSupplierAccount->id,
                    'type' => 'expense',
                    'amount' => $totalValue,
                    'description' => "Facture fournisseur (ventes consignées) pour rapport #{$report->id}",
                    'document' => $fileName,
                ]);

                // 2️⃣ Comptabilité du warehouse : créance client
                $warehouse->journals()->create([
                    'date' => now(),
                    'account_id' => $warehouseReceivableAccount->id,
                    'type' => 'income',
                    'amount' => $totalValue,
                    'description' => "Vente consignée au shop {$shop->name} via rapport #{$report->id}",
                    'document' => $fileName,
                ]);
            }
        });

        return redirect()->route('resellers.show', $reseller)
            ->with('success', 'Sales report recorded, stock updated, invoice generated, and accounting entries created.');
    }


    public function show(Reseller $reseller, ResellerSalesReport $report)
    {
        // Charge les items et leurs produits
        $report->load('items.product');

        // Charge la facture associée et ses paiements
        $report->load(['invoice.payments']);

        // Calcul des paiements
        $totalPaid = $report->invoice?->payments->sum('amount') ?? 0;
        $remaining = max(($report->invoice?->total_amount ?? 0) - $totalPaid, 0);

        // Statut de paiement
        if (!$report->invoice) {
            $paymentStatus = 'N/A';
        } elseif ($remaining <= 0) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partially_paid';
        } else {
            $paymentStatus = 'unpaid';
        }

        $paymentsCount = $report->invoice?->payments->count() ?? 0;

        return view('resellers.reports.show', compact(
            'reseller',
            'report',
            'totalPaid',
            'remaining',
            'paymentStatus',
            'paymentsCount'
        ));
    }



        public function invoice(Reseller $reseller, ResellerSalesReport $report)
        {
            $report->load('items.product');

            // Vérifie si une facture existe déjà
            $invoice = ResellerInvoice::where('reseller_id', $reseller->id)
                ->where('reseller_stock_delivery_id', null) // Pas liée à une livraison
                ->where('sales_report_id', $report->id) // à ajouter dans la table si tu veux lier report -> invoice
                ->first();

            $totalValue = $report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price);

            if (!$invoice) {
                // Crée une nouvelle facture
                $invoice = ResellerInvoice::create([
                    'reseller_id' => $reseller->id,
                    'reseller_stock_delivery_id' => null,
                    'total_amount' => $totalValue,
                    'status' => 'unpaid',
                    'file_path' => null,
                ]);
            }

            // Génère le PDF
            $pdf = Pdf::loadView('resellers.reports.invoice', [
                'reseller' => $reseller,
                'report' => $report,
                'totalValue' => $totalValue
            ])->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            // Sauvegarde le PDF sur le disque
            $fileName = 'invoices/sales_report_' . $report->id . '.pdf';
            Storage::put($fileName, $pdf->output());

            // Mets à jour le chemin dans la facture
            $invoice->file_path = $fileName;
            $invoice->save();

            // Retourne le PDF pour téléchargement
            return $pdf->download('sales_report_' . $report->id . '.pdf');
        }

    }
