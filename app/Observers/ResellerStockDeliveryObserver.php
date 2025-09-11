<?php

namespace App\Observers;

use App\Models\ResellerStockDelivery;
use App\Models\ResellerInvoice;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ResellerStockDeliveryObserver
{
    /**
     * Handle the ResellerStockDelivery "updated" event.
     */
    public function updated(ResellerStockDelivery $delivery)
    {
        // Vérifie si le statut vient de passer à "shipped"
        if ($delivery->isDirty('status') && $delivery->status === 'shipped') {
            $this->generateInvoice($delivery);
        }
    }

    protected function generateInvoice(ResellerStockDelivery $delivery)
    {
        $entity = $delivery->reseller ?? $delivery->store;

        // Si aucune entité trouvée, on sort
        if (!$entity) {
            return;
        }

        $entityType = $delivery->reseller ? 'reseller' : 'store';
        $entityId   = $entity->id;

        // Vérifie si la facture existe déjà
        $invoice = ResellerInvoice::firstOrCreate(
            ['reseller_stock_delivery_id' => $delivery->id],
            [
                'reseller_id'  => $entityType === 'reseller' ? $entityId : null,
                'store_id'     => $entityType === 'store' ? $entityId : null,
                'total_amount' => 0, // temporaire
                'status'       => 'unpaid',
            ]
        );

        $delivery->load('products');

        $itemsTotal = $delivery->products->sum(
            fn($p) => ($p->pivot->quantity ?? 0) * ($p->pivot->unit_price ?? 0)
        );
        $shipping    = $delivery->shipping_cost ?? 0;
        $totalAmount = $itemsTotal + $shipping;

        $invoice->update(['total_amount' => $totalAmount]);

        // Génération PDF —> dossier séparé suivant le type
        $view = $entityType === 'reseller' ? 'invoices.buyer' : 'invoices.shop';
        $folder = $entityType === 'reseller' ? 'buyers' : 'shops';

        $pdf = Pdf::loadView($view, [
            'delivery' => $delivery,
            'invoice'  => $invoice,
        ]);

        $fileName = "invoice_{$invoice->id}.pdf";
        $filePath = "{$folder}/{$fileName}";

        Storage::disk('invoices')->put($filePath, $pdf->output());
        $invoice->update(['file_path' => $filePath]);
    }
}
