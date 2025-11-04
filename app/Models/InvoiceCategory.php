<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceCategory extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    public function generalInvoices(): HasMany
    {
        return $this->hasMany(GeneralInvoice::class, 'category_id');
    }
}
