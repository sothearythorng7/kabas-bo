<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'activation_mode',
        'priority',
        'is_exclusive',
        'stackable_group',
        'conditions_logic',
        'channel',
        'starts_at',
        'ends_at',
        'max_uses_total',
        'max_uses_per_customer',
        'usage_count',
        'max_budget',
        'budget_consumed',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_exclusive' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_uses_total' => 'integer',
            'max_uses_per_customer' => 'integer',
            'usage_count' => 'integer',
            'max_budget' => 'decimal:5',
            'budget_consumed' => 'decimal:5',
            'priority' => 'integer',
        ];
    }

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived';

    public const MODE_AUTOMATIC = 'automatic';
    public const MODE_CODE_REQUIRED = 'code_required';

    public const LOGIC_ALL = 'all';
    public const LOGIC_ANY = 'any';

    public const CHANNEL_WEBSITE = 'website';
    public const CHANNEL_POS = 'pos';
    public const CHANNEL_BOTH = 'both';

    public function conditions()
    {
        return $this->hasMany(PromotionCondition::class)->orderBy('position');
    }

    public function actions()
    {
        return $this->hasMany(PromotionAction::class)->orderBy('position');
    }

    public function codes()
    {
        return $this->hasMany(PromotionCode::class);
    }

    public function usages()
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
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

    public function scopeAutomatic($query)
    {
        return $query->where('activation_mode', self::MODE_AUTOMATIC);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->whereIn('channel', [$channel, self::CHANNEL_BOTH]);
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
        if ($this->max_uses_total === null) {
            return true;
        }

        return $this->usage_count < $this->max_uses_total;
    }

    public function hasBudgetLeft(): bool
    {
        if ($this->max_budget === null) {
            return true;
        }

        return (float) $this->budget_consumed < (float) $this->max_budget;
    }
}
