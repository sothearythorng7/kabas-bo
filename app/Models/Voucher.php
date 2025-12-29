<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'amount',
        'status',
        'source_type',
        'source_exchange_id',
        'used_at',
        'used_in_sale_id',
        'used_at_store_id',
        'expires_at',
        'created_by_user_id',
        'created_at_store_id',
        'cancelled_by_user_id',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Generate a unique voucher code (KBA + 9 digits)
     */
    public static function generateCode(): string
    {
        do {
            $code = 'KBA' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Check if voucher is valid for use
     */
    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    /**
     * Scope for active vouchers
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired vouchers that need status update
     */
    public function scopeNeedsExpiration($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<', now());
    }

    // Relationships

    public function sourceExchange()
    {
        return $this->belongsTo(Exchange::class, 'source_exchange_id');
    }

    public function usedInSale()
    {
        return $this->belongsTo(Sale::class, 'used_in_sale_id');
    }

    public function usedAtStore()
    {
        return $this->belongsTo(Store::class, 'used_at_store_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdAtStore()
    {
        return $this->belongsTo(Store::class, 'created_at_store_id');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
}
