<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseInvoiceStatusHistory extends Model
{
    protected $fillable = ['warehouse_invoice_id', 'status'];

    public function invoice()
    {
        return $this->belongsTo(WarehouseInvoice::class, 'warehouse_invoice_id');
    }
}
