<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerStockReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'store_id',
        'destination_store_id',
        'user_id',
        'status',
        'note',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_VALIDATED = 'validated';
    const STATUS_CANCELLED = 'cancelled';

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function destinationStore()
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ResellerStockReturnItem::class);
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }
}
