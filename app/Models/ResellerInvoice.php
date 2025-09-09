<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerInvoice extends Model
{
    protected $table = 'resellers_invoices';

    protected $fillable = [
        'reseller_id',
        'reseller_stock_delivery_id',
        'total_amount',
        'status',
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
}
