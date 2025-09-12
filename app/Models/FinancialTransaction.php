<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'store_id', 'account_id', 'reseller_invoice_id',
        'amount', 'currency', 'direction',
        'balance_before', 'balance_after',
        'label', 'description', 'status', 'transaction_date',
        'payment_method_id', 'user_id', 'external_reference'
    ];

    public function account()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(FinancialPaymentMethod::class);
    }

    public function attachments()
    {
        return $this->hasMany(FinancialTransactionAttachment::class, 'transaction_id');
    }

    public function logs()
    {
        return $this->hasMany(FinancialTransactionLog::class, 'transaction_id');
    }
}
