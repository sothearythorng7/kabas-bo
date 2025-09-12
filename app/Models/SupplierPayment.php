<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'supplier_id',
        'reference',
        'amount',
        'due_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
