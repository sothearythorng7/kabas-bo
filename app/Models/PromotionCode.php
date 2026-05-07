<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionCode extends Model
{
    protected $fillable = [
        'promotion_rule_id',
        'code',
        'max_uses',
        'usage_count',
        'per_customer_limit',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses' => 'integer',
            'usage_count' => 'integer',
            'per_customer_limit' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->code) {
                $model->code = strtoupper(trim($model->code));
            }
        });
    }

    public function rule()
    {
        return $this->belongsTo(PromotionRule::class, 'promotion_rule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function usages()
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithinDates($query, ?\DateTimeInterface $now = null)
    {
        $now = $now ?? now();

        return $query
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function isWithinDates(?\DateTimeInterface $now = null): bool
    {
        $now = $now ?? now();

        if ($this->starts_at && $this->starts_at->greaterThan($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lessThan($now)) {
            return false;
        }

        return true;
    }

    public function hasUsesLeft(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }

        return $this->usage_count < $this->max_uses;
    }
}
