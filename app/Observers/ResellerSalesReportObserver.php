<?php

namespace App\Observers;

use App\Models\ResellerSalesReport;
use App\Models\ResellerInvoice;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ResellerSalesReportObserver
{
    /**
     * Handle the ResellerSalesReport "created" event.
     */
    public function created(ResellerSalesReport $report)
    {
        // Seulement pour les revendeurs de type consignment
        if ($report->reseller->type !== 'consignment') {
            return;
        }

        $this->generateInvoice($report);
    }

    protected function generateInvoice(ResellerSalesReport $report)
    {
        // Charger les items du rapport
        $report->load('items.product');

        // Calcul du total
        $totalValue = $report->items->sum(fn($item) => $item->quantity_sold * $item->unit_price);

        // Vérifie si une facture existe déjà pour ce rapport
        $invoice = ResellerInvoice::firstOrCreate(
            [
                'reseller_id' => $report->reseller_id,
                'reseller_stock_delivery_id' => null, // pas liée à une livraison
                'sales_report_id' => $report->id,
            ],
            [
                'total_amount' => $totalValue,
                'status' => 'unpaid',
                'file_path' => null,
            ]
        );

        $report->load('items.product');
        $invoice->update(['total_amount' => $totalValue]);

        // Génération du PDF
        $pdf = Pdf::loadView('resellers.reports.invoice', [
            'reseller' => $report->reseller,
            'report' => $report,
            'totalValue' => $totalValue,
        ]);

        $fileName = "sales_report_{$report->id}.pdf";
        $filePath = "sales_reports/{$fileName}";

        Storage::put($filePath, $pdf->output());

        $invoice->update(['file_path' => $filePath]);
    }
}
