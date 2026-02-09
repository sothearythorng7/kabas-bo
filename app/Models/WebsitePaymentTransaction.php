<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'order_id',
        'tran_id',
        'payment_option',
        'amount',
        'currency',
        'status',
        'status_label',
        'apv',
        'raw_response',
        'internal_status',
        'paid_at',
        'refunded_at',
        'refund_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(WebsiteOrder::class, 'order_id');
    }

    public function getStatusDescriptionAttribute(): string
    {
        return match ($this->status) {
            '0' => 'Approved',
            '1' => 'Created',
            '2' => 'Pending',
            '3' => 'Declined',
            '4' => 'Refunded',
            '5' => 'Error',
            '6' => 'Cancelled',
            '7' => 'Expired',
            '11' => 'Awaiting PopUp',
            default => 'Unknown',
        };
    }

    public static function internalStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'initiated' => 'secondary',
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'dark',
            'refunded' => 'info',
            default => 'light',
        };
    }
}
