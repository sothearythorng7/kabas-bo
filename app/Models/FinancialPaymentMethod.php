<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialPaymentMethod extends Model
{
    protected $fillable = ['name', 'code'];

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'payment_method_id');
    }
}
