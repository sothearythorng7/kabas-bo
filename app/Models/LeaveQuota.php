<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveQuota extends Model
{
    protected $fillable = [
        'staff_member_id',
        'type',
        'year',
        'annual_quota',
        'monthly_accrual',
        'carryover_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'annual_quota' => 'decimal:2',
        'monthly_accrual' => 'decimal:2',
        'carryover_days' => 'decimal:2',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function getAccruedDays(): float
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        if ($this->year < $currentYear) {
            // Past year: full annual quota
            return $this->annual_quota + $this->carryover_days;
        }

        if ($this->year > $currentYear) {
            // Future year: only carryover
            return $this->carryover_days;
        }

        // Current year: monthly accrual up to current month
        return min(
            ($this->monthly_accrual * $currentMonth) + $this->carryover_days,
            $this->annual_quota + $this->carryover_days
        );
    }

    public function getUsedDays(): float
    {
        return $this->leaves()
            ->whereIn('status', ['approved', 'pending'])
            ->whereYear('start_date', $this->year)
            ->get()
            ->sum(function ($leave) {
                return $leave->getDaysCount();
            });
    }

    public function getRemainingDays(): float
    {
        return max(0, $this->getAccruedDays() - $this->getUsedDays());
    }

    public function getTypeBadgeClass(): string
    {
        return match($this->type) {
            'vacation' => 'primary',
            'sick' => 'warning',
            'dayoff' => 'info',
            default => 'secondary',
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'vacation' => __('messages.staff.leave_types.vacation'),
            'sick' => __('messages.staff.leave_types.sick'),
            'dayoff' => __('messages.staff.leave_types.dayoff'),
            default => $this->type,
        };
    }
}
