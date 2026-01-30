<?php

namespace App\Console\Commands;

use App\Models\SaleReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Storage;

class RegenerateSaleReportPdf extends Command
{
    protected $signature = 'sale-report:regenerate-pdf {id}';
    protected $description = 'Regenerate the PDF for a sale report';

    public function handle()
    {
        $id = $this->argument('id');
        $saleReport = SaleReport::with('items.product', 'supplier', 'store')->find($id);

        if (!$saleReport) {
            $this->error("Sale report #{$id} not found");
            return 1;
        }

        $this->info("Regenerating PDF for Sale Report #{$id}...");

        $pdf = Pdf::loadView('sale_reports.pdf', [
            'saleReport' => $saleReport
        ]);

        $supplierName = Str::slug($saleReport->supplier->name, '_');
        $dateStart = $saleReport->period_start->format('dmY');
        $dateEnd = $saleReport->period_end->format('dmY');
        $filename = strtoupper("{$supplierName}_{$dateStart}_{$dateEnd}") . '.pdf';

        $path = "sale_reports/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        $saleReport->update(['report_file_path' => $path]);

        $this->info("PDF generated: {$path}");
        $this->info("URL: " . Storage::url($path));

        return 0;
    }
}
