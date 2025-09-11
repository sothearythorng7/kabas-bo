<?php

namespace App\Http\Controllers;

use App\Models\ResellerInvoice;
use App\Models\ResellerStockDelivery;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Génère ou télécharge une facture.
     */
    public function generateOrDownload(ResellerStockDelivery $delivery)
    {
        // Vérifie qu'il y a bien un reseller buyer
        if ($delivery->reseller->type !== 'buyer') {
            abort(403, "Invoices can only be generated for buyers.");
        }

        // Vérifie si une facture existe déjà
        $invoice = ResellerInvoice::where('reseller_stock_delivery_id', $delivery->id)->first();

        if ($invoice && $invoice->file_path && Storage::disk('invoices')->exists($invoice->file_path)) {
            // Déjà générée → téléchargement
            return Storage::disk('invoices')->download($invoice->file_path);
        }

        // Sinon on génère la facture
        $totalAmount = $delivery->products->sum(function ($p) {
            return $p->pivot->quantity * $p->pivot->unit_price;
        }) + $delivery->shipping_cost;

        // Crée la facture en BDD
        $invoice = ResellerInvoice::create([
            'reseller_id' => $delivery->reseller_id,
            'reseller_stock_delivery_id' => $delivery->id,
            'total_amount' => $totalAmount,
            'status' => 'unpaid',
        ]);

        // Génération du PDF
        $pdf = Pdf::loadView('invoices.buyer', [
            'delivery' => $delivery,
            'invoice' => $invoice,
        ]);

        $fileName = "invoice_{$invoice->id}.pdf";
        $filePath = "buyers/{$fileName}";

        Storage::disk('invoices')->put($filePath, $pdf->output());

        $invoice->update(['file_path' => $filePath]);

        return Storage::disk('invoices')->download($filePath);
    }

    public function show(ResellerInvoice $invoice)
    {
        // Vérifie que le fichier existe
        if (!Storage::disk('invoices')->exists($invoice->file_path)) {
            abort(404, 'Invoice not found.');
        }

        // Retourne le fichier en téléchargement
        return Storage::disk('invoices')->download($invoice->file_path, 'invoice_'.$invoice->id.'.pdf');
    }

    public function stream(ResellerInvoice $invoice)
    {
        if (!Storage::disk('invoices')->exists($invoice->file_path)) {
            abort(404, 'Invoice not found.');
        }

        $file = Storage::disk('invoices')->get($invoice->file_path);

        return response($file, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="invoice_'.$invoice->id.'.pdf"');
    }
}
