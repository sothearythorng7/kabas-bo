<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeCommission extends Model
{
    protected $fillable = [
        'staff_member_id',
        'source_type',
        'source_id',
        'percentage',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function calculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'source_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'source_id');
    }

    public function getSourceName(): string
    {
        if ($this->source_id === null) {
            return $this->source_type === 'store_sales'
                ? __('messages.staff.all_stores')
                : __('messages.staff.all_resellers');
        }

        if ($this->source_type === 'store_sales') {
            return $this->store?->name ?? __('messages.unknown');
        }

        return $this->reseller?->name ?? __('messages.unknown');
    }

    public function getSourceTypeBadge(): string
    {
        return $this->source_type === 'store_sales' ? 'primary' : 'success';
    }

    public function getSourceTypeLabel(): string
    {
        return $this->source_type === 'store_sales'
            ? __('messages.staff.store_sales')
            : __('messages.staff.reseller_sales');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            });
    }

    public function isActiveForPeriod(string $period): bool
    {
        $periodStart = \Carbon\Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        if (!$this->is_active) {
            return false;
        }

        if ($this->effective_from > $periodEnd) {
            return false;
        }

        if ($this->effective_to && $this->effective_to < $periodStart) {
            return false;
        }

        return true;
    }
}
