<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryAdvance extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reason',
        'status',
        'requested_at',
        'approved_by',
        'approved_at',
        'financial_transaction_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'deducted' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('messages.staff.advance_status.pending'),
            'approved' => __('messages.staff.advance_status.approved'),
            'rejected' => __('messages.staff.advance_status.rejected'),
            'deducted' => __('messages.staff.advance_status.deducted'),
            default => $this->status,
        };
    }
}
