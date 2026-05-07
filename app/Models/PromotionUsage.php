<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionUsage extends Model
{
    protected $fillable = [
        'promotion_rule_id',
        'promotion_code_id',
        'order_id',
        'customer_id',
        'discount_amount',
        'gift_cost',
        'snapshot',
        'status',
        'applied_at',
        'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'discount_amount' => 'decimal:5',
            'gift_cost' => 'decimal:5',
            'applied_at' => 'datetime',
            'reverted_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REVERTED = 'reverted';

    public function rule()
    {
        return $this->belongsTo(PromotionRule::class, 'promotion_rule_id');
    }

    public function code()
    {
        return $this->belongsTo(PromotionCode::class, 'promotion_code_id');
    }

    public function order()
    {
        return $this->belongsTo(WebsiteOrder::class, 'order_id');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
