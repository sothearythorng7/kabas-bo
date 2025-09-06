<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseInvoiceHistory extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_invoice_id', 'user_id', 'changes'];

    protected $casts = [
        'changes' => 'array', // si tu stockes les changements en JSON
    ];

    public function invoice()
    {
        return $this->belongsTo(WarehouseInvoice::class, 'warehouse_invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
