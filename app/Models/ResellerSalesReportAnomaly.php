<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSalesReportAnomaly extends Model
{
    protected $fillable = [
        'report_id',
        'product_id',
        'quantity',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(ResellerSalesReport::class, 'report_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
