<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseInvoiceFile extends Model
{
    protected $fillable = ['warehouse_invoice_id', 'path', 'label'];

    public function invoice()
    {
        return $this->belongsTo(WarehouseInvoice::class, 'warehouse_invoice_id');
    }
}
