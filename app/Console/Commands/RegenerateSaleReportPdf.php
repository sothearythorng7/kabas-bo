<?php

namespace App\Console\Commands;

use App\Models\SaleReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storage;

class RegenerateSaleReportPdf extends Command
{
    protected $signature = 'sale-report:regenerate-pdf {id? : Sale report ID (omit for --all)} {--all : Regenerate all PDFs}';
    protected $description = 'Regenerate the PDF for a sale report';

    public function handle()
    {
        if ($this->option('all')) {
            return $this->regenerateAll();
        }

        $id = $this->argument('id');

        if (!$id) {
            $this->error('Please provide a sale report ID or use --all');
            return 1;
        }

        return $this->regenerateOne($id);
    }

    private function regenerateOne($id)
    {
        $saleReport = SaleReport::with('items.product', 'supplier', 'store')->find($id);

        if (!$saleReport) {
            $this->error("Sale report #{$id} not found");
            return 1;
        }

        $this->info("Regenerating PDF for Sale Report #{$id}...");
        $path = $this->generatePdf($saleReport);
        $this->info("PDF generated: {$path}");

        return 0;
    }

    private function regenerateAll()
    {
        $reports = SaleReport::with('items.product', 'supplier', 'store')->get();
        $this->info("Regenerating {$reports->count()} PDFs...");

        $success = 0;
        $errors = 0;

        foreach ($reports as $sr) {
            try {
                $path = $this->generatePdf($sr);
                $success++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("Report #{$sr->id}: {$e->getMessage()}");
            }
        }

        $this->info("Done: {$success} regenerated, {$errors} errors.");
        return 0;
    }

    private function generatePdf(SaleReport $saleReport): string
    {
        // Delete old PDF if exists
        if ($saleReport->report_file_path) {
            Storage::disk('public')->delete($saleReport->report_file_path);
        }

        $pdf = Pdf::loadView('sale_reports.pdf', [
            'saleReport' => $saleReport
        ])->setPaper('a4', 'landscape');

        $supplierName = Str::slug($saleReport->supplier->name, '_');
        $storeName = Str::slug($saleReport->store->name, '_');
        $dateStart = $saleReport->period_start->format('dmY');
        $dateEnd = $saleReport->period_end->format('dmY');
        $hash = substr(md5(now()->timestamp . $saleReport->id), 0, 8);
        $filename = strtoupper("{$supplierName}_{$storeName}_{$dateStart}_{$dateEnd}") . "_{$hash}.pdf";

        $path = "sale_reports/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());
        $saleReport->update(['report_file_path' => $path]);

        return $path;
    }
}
