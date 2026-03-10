<?php

namespace App\Console\Commands;

use App\Models\SaleReport;
use App\Models\SaleReportItem;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storage;

class BackfillSaleReportItems extends Command
{
    protected $signature = 'sale-reports:backfill {--report= : Process a specific report ID only}';
    protected $description = 'Add missing product items (qty 0) to existing sale reports and regenerate PDFs';

    public function handle()
    {
        $reportId = $this->option('report');

        $query = SaleReport::with(['items.product', 'supplier.products', 'store']);
        if ($reportId) {
            $query->where('id', $reportId);
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            $this->warn('No reports found.');
            return;
        }

        $this->info("Processing {$reports->count()} report(s)...");

        $totalAdded = 0;

        foreach ($reports as $report) {
            $added = $this->processReport($report);
            $totalAdded += $added;
        }

        $this->newLine();
        $this->info("Done. Added {$totalAdded} missing item(s) across {$reports->count()} report(s).");
    }

    private function processReport(SaleReport $report): int
    {
        $supplier = $report->supplier;
        if (!$supplier) {
            $this->warn("Report #{$report->id}: supplier not found, skipping.");
            return 0;
        }

        // All products of this supplier
        $allSupplierProducts = $supplier->products()->get();
        $existingProductIds = $report->items->pluck('product_id')->toArray();
        $missingProducts = $allSupplierProducts->whereNotIn('id', $existingProductIds);

        $added = 0;

        if ($missingProducts->isNotEmpty()) {
            // Calculate refill quantities for the report period
            $refillQuantities = [];
            if ($report->period_start && $report->period_end) {
                $refillQuantities = $this->getRefillQuantities(
                    $supplier->id,
                    $report->store_id,
                    $report->period_start->format('Y-m-d'),
                    $report->period_end->format('Y-m-d')
                );
            }

            // Get current stock for the store
            $currentStockQuantities = $this->getCurrentStockQuantities(
                $report->store_id,
                $missingProducts->pluck('id')->toArray()
            );

            foreach ($missingProducts as $product) {
                $refill = $refillQuantities[$product->id] ?? 0;
                $stockOnHand = $currentStockQuantities[$product->id] ?? 0;
                $oldStock = max(0, $stockOnHand - $refill);

                SaleReportItem::create([
                    'sale_report_id' => $report->id,
                    'product_id' => $product->id,
                    'old_stock' => $oldStock,
                    'refill' => $refill,
                    'stock_on_hand' => $stockOnHand,
                    'quantity_sold' => 0,
                    'unit_price' => $product->pivot->purchase_price ?? 0,
                    'selling_price' => 0,
                    'total' => 0,
                ]);
                $added++;
            }

            // Reload items for PDF
            $report->load('items.product');
        }

        // Regenerate PDF
        $report->load('supplier', 'store');

        $pdf = Pdf::loadView('sale_reports.pdf', [
            'saleReport' => $report
        ])->setPaper('a4', 'landscape');

        $supplierName = Str::slug($report->supplier->name, '_');
        $storeName = Str::slug($report->store->name, '_');
        $dateStart = $report->period_start->format('dmY');
        $dateEnd = $report->period_end->format('dmY');
        $filename = strtoupper("{$supplierName}_{$storeName}_{$dateStart}_{$dateEnd}") . '.pdf';

        $path = "sale_reports/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        $report->update(['report_file_path' => $path]);

        $status = $added > 0 ? "+{$added} items" : "no changes";
        $this->line("  Report #{$report->id} ({$report->supplier->name}): {$status} → PDF regenerated");

        return $added;
    }

    private function getRefillQuantities(int $supplierId, int $storeId, string $periodStart, string $periodEnd): array
    {
        $quantities = [];

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

        return $quantities;
    }

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
}
