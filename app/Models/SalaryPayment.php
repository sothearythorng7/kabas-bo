<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SalaryPayment extends Model
{
    protected $fillable = [
        'staff_member_id',
        'period',
        'base_salary',
        'daily_rate',
        'unjustified_days',
        'absence_deduction',
        'advances_deduction',
        'overtime_amount',
        'bonus_amount',
        'penalty_amount',
        'commission_amount',
        'gross_salary',
        'net_amount',
        'currency',
        'notes',
        'paid_by',
        'store_id',
        'financial_transaction_id',
        'is_transferred',
        'transferred_at',
        'transfer_reference',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'advances_deduction' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_transferred' => 'boolean',
        'transferred_at' => 'datetime',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        return Carbon::parse($this->period . '-01')->translatedFormat('F Y');
    }

    public function getTotalDeductionsAttribute(): float
    {
        return $this->absence_deduction + $this->advances_deduction + $this->penalty_amount;
    }

    public function getTotalAdditionsAttribute(): float
    {
        return $this->overtime_amount + $this->bonus_amount + $this->commission_amount;
    }

    public function getGrossSalaryCalculatedAttribute(): float
    {
        return $this->base_salary + $this->total_additions;
    }
}
