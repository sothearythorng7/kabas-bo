<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CommissionCalculation extends Model
{
    protected $fillable = [
        'staff_member_id',
        'employee_commission_id',
        'period',
        'base_amount',
        'commission_amount',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'base_amount' => 'decimal:5',
        'commission_amount' => 'decimal:5',
        'approved_at' => 'datetime',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function employeeCommission(): BelongsTo
    {
        return $this->belongsTo(EmployeeCommission::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        return Carbon::parse($this->period . '-01')->translatedFormat('F Y');
    }

    public function getSalesPeriodLabelAttribute(): string
    {
        return Carbon::parse($this->period . '-01')->subMonth()->translatedFormat('F Y');
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'paid' => 'primary',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('messages.staff.commission_status.pending'),
            'approved' => __('messages.staff.commission_status.approved'),
            'paid' => __('messages.staff.commission_status.paid'),
            default => $this->status,
        };
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
