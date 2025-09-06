<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\InvoiceStatus;

class WarehouseInvoice extends Model
{
    protected $fillable = [
        'creditor_name',
        'description',
        'type',
        'status',
        'status_history',
        'creditor_invoice_number',
        'amount_usd',
        'amount_riel',
        'internal_payment_number',
        'payment_type',
        'attachment_path',
    ];

    protected $casts = [
        'status_history' => 'array',
        'status_history' => 'array',
        'type' => \App\Enums\InvoiceType::class,
        'status' => \App\Enums\InvoiceStatus::class,
        'payment_type' => \App\Enums\PaymentType::class
    ];


    // Conversion automatique entre USD ↔ Riel
    public function setAmountUsdAttribute($value)
    {
        $this->attributes['amount_usd'] = $value;
        $this->attributes['amount_riel'] = $value * 4000;
    }

    public function setAmountRielAttribute($value)
    {
        $this->attributes['amount_riel'] = $value;
        $this->attributes['amount_usd'] = $value / 4000;
    }

    // Historisation automatique du statut
    public function setStatusAttribute($value)
    {
        $history = $this->status_history ?? [];
        $history[] = [
            'status' => $value,
            'date' => now()->toDateTimeString(),
        ];
        $this->attributes['status'] = $value;
        $this->attributes['status_history'] = json_encode($history);
    }

    public function statusHistories()
    {
        return $this->hasMany(WarehouseInvoiceStatusHistory::class, 'warehouse_invoice_id')->orderBy('created_at', 'asc');
    }

    /**
     * Ajoute une entrée d'historique
     */
    public function addStatusHistory(string $status)
    {
        return $this->statusHistories()->create(['status' => $status]);
    }

    public function files()
    {
        return $this->hasMany(WarehouseInvoiceFile::class);
    }

    public function histories()
    {
        return $this->hasMany(WarehouseInvoiceHistory::class);
    }
}
