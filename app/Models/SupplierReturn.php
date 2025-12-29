<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    protected $fillable = [
        'supplier_id',
        'store_id',
        'created_by_user_id',
        'status',
        'notes',
        'pdf_path',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getTotalValueAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isValidated()
    {
        return $this->status === 'validated';
    }

    public function isEditable()
    {
        return $this->status === 'pending';
    }
}
