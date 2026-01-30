<?php

namespace App\Console\Commands;

use App\Models\SaleReport;
use App\Models\SaleReportItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storage;

class RecalculateSaleReportSellingPrices extends Command
{
    protected $signature = 'sale-report:recalculate-all {--dry-run : Show what would be done without making changes}';
    protected $description = 'Recalculate selling_price for all sale report items and regenerate PDFs';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Fetching all sale report items...');

        $items = SaleReportItem::with('product', 'saleReport.supplier')->get();
        $updatedCount = 0;
        $skippedCount = 0;
        $reportIds = [];

        $this->output->progressStart($items->count());

        foreach ($items as $item) {
            $product = $item->product;

            if (!$product) {
                $this->newLine();
                $this->warn("  Item #{$item->id}: Product not found, skipping");
                $skippedCount++;
                $this->output->progressAdvance();
                continue;
            }

            $productPrice = $product->price ?? 0;

            if ($productPrice == 0) {
                $this->newLine();
                $this->warn("  Item #{$item->id}: Product '{$product->ean}' has no price (price=0), skipping");
                $skippedCount++;
                $this->output->progressAdvance();
                continue;
            }

            $newSellingPrice = $productPrice * $item->quantity_sold;
            $oldSellingPrice = $item->selling_price;

            if ($oldSellingPrice != $newSellingPrice) {
                if (!$dryRun) {
                    $item->update(['selling_price' => $newSellingPrice]);
                }
                $updatedCount++;
                $reportIds[$item->sale_report_id] = true;

                $this->newLine();
                $this->line("  Item #{$item->id}: {$oldSellingPrice} -> {$newSellingPrice} (product price: {$productPrice}, qty: {$item->quantity_sold})");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->newLine();

        $this->info("Updated {$updatedCount} items, skipped {$skippedCount} items");

        // Regenerate PDFs for affected reports
        $reportIdsToRegenerate = array_keys($reportIds);

        if (count($reportIdsToRegenerate) > 0) {
            $this->newLine();
            $this->info('Regenerating PDFs for ' . count($reportIdsToRegenerate) . ' reports...');

            foreach ($reportIdsToRegenerate as $reportId) {
                $saleReport = SaleReport::with('items.product', 'supplier', 'store')->find($reportId);

                if (!$saleReport || !$saleReport->supplier || !$saleReport->store) {
                    $this->warn("  Report #{$reportId}: Missing supplier or store, skipping PDF");
                    continue;
                }

                if (!$dryRun) {
                    $pdf = Pdf::loadView('sale_reports.pdf', [
                        'saleReport' => $saleReport
                    ])->setPaper('a4', 'landscape');

                    $supplierName = Str::slug($saleReport->supplier->name, '_');
                    $dateStart = $saleReport->period_start->format('dmY');
                    $dateEnd = $saleReport->period_end->format('dmY');
                    $filename = strtoupper("{$supplierName}_{$dateStart}_{$dateEnd}") . '.pdf';

                    $path = "sale_reports/{$filename}";
                    Storage::disk('public')->put($path, $pdf->output());

                    $saleReport->update(['report_file_path' => $path]);

                    $this->info("  Report #{$reportId}: PDF regenerated -> {$path}");
                } else {
                    $this->info("  Report #{$reportId}: Would regenerate PDF");
                }
            }
        }

        $this->newLine();
        $this->info('Done!');

        return 0;
    }
}
