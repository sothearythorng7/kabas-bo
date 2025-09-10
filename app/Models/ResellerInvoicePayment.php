<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerInvoicePayment extends Model
{
    protected $table = 'resellers_invoice_payments';

    protected $fillable = [
        'resellers_invoice_id',
        'amount',
        'paid_at',
        'payment_method',
        'reference',
    ];

    /**
     * Facture à laquelle ce paiement est rattaché
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ResellerInvoice::class, 'resellers_invoice_id');
    }

    /**
     * Met à jour automatiquement le statut de la facture après un paiement
     */
    protected static function booted()
    {
        static::saved(function ($payment) {
            $payment->invoice->updateStatus();
        });

        static::deleted(function ($payment) {
            $payment->invoice->updateStatus();
        });
    }

    protected $casts = [
        'paid_at' => 'datetime',
    ];
}
