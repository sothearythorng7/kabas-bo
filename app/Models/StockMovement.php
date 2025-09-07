<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',           // 'transfer', 'in', 'out', 'adjustment'
        'from_store_id',
        'to_store_id',
        'note',
        'user_id',
        'status',
    ];

    public const STATUS_DRAFT      = 'draft';
    public const STATUS_VALIDATED  = 'validated';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED   = 'received';
    public const STATUS_CANCELLED  = 'cancelled';

    public const TYPE_TRANSFER   = 'transfer';
    public const TYPE_IN         = 'in';
    public const TYPE_OUT        = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public function items()
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

