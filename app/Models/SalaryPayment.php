<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SalaryPayment extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'base_salary',
        'daily_rate',
        'unjustified_days',
        'absence_deduction',
        'advances_deduction',
        'net_amount',
        'currency',
        'notes',
        'paid_by',
        'store_id',
        'financial_transaction_id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'advances_deduction' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $this->absence_deduction + $this->advances_deduction;
    }
}
