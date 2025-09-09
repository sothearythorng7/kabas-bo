<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSalesReport extends Model
{
    protected $fillable = [
        'reseller_id',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerSalesReportItem::class, 'report_id');
    }
}
