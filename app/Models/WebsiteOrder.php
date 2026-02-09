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
        'payment_method',
        'payway_tran_id',
        'customer_notes',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
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
