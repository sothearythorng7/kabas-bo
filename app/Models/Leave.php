<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Leave extends Model
{
    protected $fillable = [
        'staff_member_id',
        'type',
        'start_date',
        'end_date',
        'start_half_day',
        'end_half_day',
        'leave_quota_id',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_half_day' => 'boolean',
        'end_half_day' => 'boolean',
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

    public function leaveQuota(): BelongsTo
    {
        return $this->belongsTo(LeaveQuota::class);
    }

    public function getDaysCount(): float
    {
        $days = $this->start_date->diffInDays($this->end_date) + 1;

        // Subtract half days if applicable
        if ($this->start_half_day) {
            $days -= 0.5;
        }
        if ($this->end_half_day) {
            $days -= 0.5;
        }

        return max(0.5, $days);
    }

    public function getTypeBadgeClass(): string
    {
        return match($this->type) {
            'vacation' => 'primary',
            'dayoff' => 'info',
            'sick' => 'warning',
            'unjustified' => 'danger',
            default => 'secondary',
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'vacation' => __('messages.staff.leave_types.vacation'),
            'dayoff' => __('messages.staff.leave_types.dayoff'),
            'sick' => __('messages.staff.leave_types.sick'),
            'unjustified' => __('messages.staff.leave_types.unjustified'),
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
            'pending' => __('messages.staff.leave_status.pending'),
            'approved' => __('messages.staff.leave_status.approved'),
            'rejected' => __('messages.staff.leave_status.rejected'),
            default => $this->status,
        };
    }
}
