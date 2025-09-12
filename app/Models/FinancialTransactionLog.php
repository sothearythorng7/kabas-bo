<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionLog extends Model
{
    protected $fillable = ['transaction_id', 'action', 'old_values', 'new_values', 'performed_by'];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
