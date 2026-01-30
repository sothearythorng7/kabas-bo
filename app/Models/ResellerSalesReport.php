<?php

namespace App\Models;
use App\Models\ResellerInvoice;

use Illuminate\Database\Eloquent\Model;

class ResellerSalesReport extends Model
{
    protected $fillable = [
        'reseller_id', 'store_id', 'start_date', 'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerSalesReportItem::class, 'report_id');
    }

    public function totalAmount()
    {
        return $this->items->sum(function($item) {
            return $item->quantity_sold * $item->unit_price;
        });
    }

    // Relation vers la facture (si tu as un modèle Invoice)
    public function invoice()
    {
        return $this->hasOne(ResellerInvoice::class, 'sales_report_id');
    }
}
