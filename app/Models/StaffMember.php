<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StaffMember extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'hire_date',
        'store_id',
        'contract_status',
        'contract_end_date',
        'termination_reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'contract_end_date' => 'date',
        ];
    }

    // ==================== Core Relations ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ==================== HR Relations ====================

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(UserSalary::class)->orderByDesc('effective_from');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(UserSalary::class)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->orderByDesc('id');
    }

    public function salaryAdvances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class)->orderByDesc('requested_at');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class)->orderByDesc('start_date');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(UserSchedule::class)->orderBy('day_of_week');
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class)->orderByDesc('period');
    }

    public function leaveQuotas(): HasMany
    {
        return $this->hasMany(LeaveQuota::class);
    }

    public function employeeCommissions(): HasMany
    {
        return $this->hasMany(EmployeeCommission::class);
    }

    public function commissionCalculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    public function salaryAdjustments(): HasMany
    {
        return $this->hasMany(SalaryAdjustment::class)->orderByDesc('created_at');
    }

    // ==================== Helpers ====================

    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    public function canUsePOS(): bool
    {
        return $this->hasUserAccount() && $this->user?->pin_code !== null;
    }
}
