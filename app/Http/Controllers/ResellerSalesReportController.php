<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use App\Models\ResellerSalesReport;
use App\Models\ResellerSalesReportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        if ($reseller->type !== 'consignment') {
            abort(403, 'Only consignment resellers can create sales reports.');
        }

        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($reseller, $validated) {
            // Crée le rapport
            $report = ResellerSalesReport::create([
                'reseller_id' => $reseller->id
            ]);

            $productsData = \App\Models\Product::whereIn('id', collect($validated['products'])->pluck('id'))->get()->keyBy('id');

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

                        // Déduction du stock via le système de lots (FIFO)
                        $remaining = $p['quantity'];
                        $batches = $reseller->stockBatches()
                            ->where('product_id', $product->id)
                            ->where('quantity', '>', 0)
                            ->orderBy('created_at') // FIFO
                            ->get();

                        foreach ($batches as $batch) {
                            if ($remaining <= 0) break;

                            $deduct = min($batch->quantity, $remaining);
                            $batch->quantity -= $deduct;
                            $batch->save();

                            $remaining -= $deduct;
                        }

                        // Si le restant > 0, on crée une anomalie au lieu de bloquer
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
            });

            return redirect()->route('resellers.show', $reseller)
                ->with('success', 'Sales report recorded and stock updated.');
        }


        public function show(Reseller $reseller, ResellerSalesReport $report)
        {
            $report->load('items.product');
            return view('resellers.reports.show', compact('reseller', 'report'));
        }

    }
