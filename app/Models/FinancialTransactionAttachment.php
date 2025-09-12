<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransactionAttachment extends Model
{
    protected $fillable = ['transaction_id', 'path', 'file_type', 'uploaded_by'];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
