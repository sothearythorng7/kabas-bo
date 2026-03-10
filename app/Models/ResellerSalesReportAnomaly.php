<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSalesReportAnomaly extends Model
{
    protected $fillable = [
        'report_id',
        'product_id',
        'quantity',
        'reported_quantity',
        'accepted_quantity',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(ResellerSalesReport::class, 'report_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
