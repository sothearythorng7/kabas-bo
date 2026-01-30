<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDaysCount(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
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
