<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerInvoice extends Model
{
    // ⚠️ Vérifie bien le nom de ta table. Migration = reseller_invoices
    protected $table = 'resellers_invoices';

    protected $fillable = [
        'reseller_id',
        'store_id',
        'reseller_stock_delivery_id',
        'sales_report_id',
        'total_amount',
        'status',
        'file_path',
        'paid_at',
    ];

    /**
     * Paiements liés à cette facture
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ResellerInvoicePayment::class, 'resellers_invoice_id');
    }

    /**
     * La commande revendeur liée à cette facture
     */
    public function resellerStockDelivery(): BelongsTo
    {
        return $this->belongsTo(ResellerStockDelivery::class);
    }

    /**
     * Le revendeur lié à cette facture
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Le store lié à cette facture
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Rapport de ventes lié
     */
    public function salesReport(): BelongsTo
    {
        return $this->belongsTo(ResellerSalesReport::class, 'sales_report_id');
    }

    /**
     * Recalcule le statut de la facture selon la somme des paiements
     */
    public function updateStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');

        if ($totalPaid >= $this->total_amount) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($totalPaid > 0) {
            $this->status = 'partially_paid';
        } else {
            $this->status = 'unpaid';
            $this->paid_at = null;
        }

        $this->save();
    }

    /**
     * Helper : retourne soit le Reseller, soit le Store
     */
    public function getEntity()
    {
        return $this->reseller ?? $this->store;
    }
}
