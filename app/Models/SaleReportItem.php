<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReportItem extends Model
{
    protected $fillable = [
        'sale_report_id',
        'product_id',
        'quantity_sold',
        'unit_price',
        'total',
    ];

    public function saleReport()
    {
        return $this->belongsTo(SaleReport::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
