<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralInvoice extends Model
{
    use HasFactory;

    protected $table = 'general_invoices';

    protected $fillable = [
        'store_id',
        'account_id',
        'label',
        'amount',
        'due_date',
        'status',
        'attachment',
        'note',
        'transaction_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * La facture appartient à un site.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * La facture est liée à un compte financier.
     */
    public function account()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    /**
     * La facture peut être liée à une transaction lorsqu'elle est payée.
     */
    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    /**
     * Scope pour les factures impayées.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour les factures payées.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
