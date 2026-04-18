<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsiteOrder extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'customer_id',
        'guest_email',
        'guest_phone',
        'locale',
        'store_id',
        'source',
        'created_by_user_id',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_company',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_postal_code',
        'shipping_state',
        'shipping_country',
        'shipping_phone',
        'subtotal',
        'shipping_cost',
        'tax',
        'discount',
        'total',
        'status',
        'payment_status',
        'paid_at',
        'payment_method',
        'payment_type',
        'payway_tran_id',
        'deposit_amount',
        'deposit_paid',
        'customer_notes',
        'admin_notes',
        'payment_link_url',
        'payment_link_expires_at',
        'payment_token',
        'tracking_url',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:5',
            'shipping_cost' => 'decimal:5',
            'tax' => 'decimal:5',
            'discount' => 'decimal:5',
            'total' => 'decimal:5',
            'deposit_amount' => 'decimal:5',
            'deposit_paid' => 'boolean',
            'paid_at' => 'datetime',
            'payment_link_expires_at' => 'datetime',
        ];
    }

    // Relations
    public function items()
    {
        return $this->hasMany(WebsiteOrderItem::class, 'order_id');
    }

    public function transactions()
    {
        return $this->hasMany(WebsitePaymentTransaction::class, 'order_id');
    }

    public function latestTransaction()
    {
        return $this->hasOne(WebsitePaymentTransaction::class, 'order_id')->latestOfMany();
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Remaining balance after deposit
    public function getRemainingBalanceAttribute(): float
    {
        if (!$this->deposit_paid || $this->deposit_amount <= 0) {
            return (float) $this->total;
        }
        return max(0, (float) $this->total - (float) $this->deposit_amount);
    }

    // Accessors
    public function getContactEmailAttribute()
    {
        return $this->guest_email ?? null;
    }

    public function getShippingFullNameAttribute()
    {
        return trim($this->shipping_first_name . ' ' . $this->shipping_last_name);
    }

    public function getShippingFullAddressAttribute()
    {
        $parts = array_filter([
            $this->shipping_address_line1,
            $this->shipping_address_line2,
            $this->shipping_postal_code . ' ' . $this->shipping_city,
            $this->shipping_state,
            $this->shipping_country,
        ]);

        return implode(', ', $parts);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('order_number', 'like', "%{$search}%")
              ->orWhere('guest_email', 'like', "%{$search}%")
              ->orWhere('shipping_first_name', 'like', "%{$search}%")
              ->orWhere('shipping_last_name', 'like', "%{$search}%");
        });
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    // Special order helpers
    public function getIsSpecialOrderAttribute(): bool
    {
        return $this->source === 'backoffice';
    }

    public function getPaymentLinkExpiredAttribute(): bool
    {
        return $this->payment_link_expires_at && $this->payment_link_expires_at->isPast();
    }

    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $last = static::withTrashed()
            ->where('order_number', 'like', "ORD-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING(order_number, -5) AS UNSIGNED) DESC')
            ->value('order_number');

        $nextNum = 1;
        if ($last) {
            $lastNum = (int) substr($last, -5);
            $nextNum = $lastNum + 1;
        }

        return sprintf('ORD-%s-%05d', $year, $nextNum);
    }

    // Status helpers
    public static function statuses(): array
    {
        return ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    }

    public static function paymentStatuses(): array
    {
        return ['pending', 'paid', 'failed', 'refunded'];
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'primary',
            'shipped' => 'secondary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'light',
        };
    }

    public static function paymentStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'light',
        };
    }
}
