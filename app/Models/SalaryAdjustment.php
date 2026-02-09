<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SalaryAdjustment extends Model
{
    protected $fillable = [
        'staff_member_id',
        'period',
        'type',
        'amount',
        'hours',
        'hourly_rate',
        'description',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        return Carbon::parse($this->period . '-01')->translatedFormat('F Y');
    }

    public function getTypeBadgeClass(): string
    {
        return match($this->type) {
            'overtime' => 'info',
            'bonus' => 'success',
            'penalty' => 'danger',
            'other' => 'secondary',
            default => 'secondary',
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'overtime' => __('messages.staff.adjustment_types.overtime'),
            'bonus' => __('messages.staff.adjustment_types.bonus'),
            'penalty' => __('messages.staff.adjustment_types.penalty'),
            'other' => __('messages.staff.adjustment_types.other'),
            default => $this->type,
        };
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('messages.staff.adjustment_status.pending'),
            'approved' => __('messages.staff.adjustment_status.approved'),
            'rejected' => __('messages.staff.adjustment_status.rejected'),
            default => $this->status,
        };
    }

    public function isDeduction(): bool
    {
        return $this->type === 'penalty';
    }

    public function getSignedAmount(): float
    {
        return $this->isDeduction() ? -abs($this->amount) : abs($this->amount);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }
}
