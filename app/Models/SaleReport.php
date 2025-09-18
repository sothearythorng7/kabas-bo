<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReport extends Model
{
    protected $fillable = [
        'supplier_id',
        'store_id',
        'period_start',
        'period_end',
        'status',
        'total_amount_theoretical',
        'total_amount_invoiced',
        'is_paid',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(SaleReportItem::class);
    }
}
