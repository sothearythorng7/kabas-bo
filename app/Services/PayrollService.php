<?php

namespace App\Services;

use App\Models\StaffMember;
use App\Models\SalaryAdjustment;
use Carbon\Carbon;

class PayrollService
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function calculatePayrollForUser(StaffMember $staffMember, string $period): array
    {
        $baseSalary = $staffMember->currentSalary?->base_salary ?? 0;
        $currency = $staffMember->currentSalary?->currency ?? 'USD';

        if ($baseSalary == 0) {
            return [
                'base_salary' => 0,
                'currency' => $currency,
                'daily_rate' => 0,
                'unjustified_days' => 0,
                'absence_deduction' => 0,
                'advances_total' => 0,
                'overtime_amount' => 0,
                'bonus_amount' => 0,
                'penalty_amount' => 0,
                'commission_amount' => 0,
                'gross_salary' => 0,
                'total_deductions' => 0,
                'total_additions' => 0,
                'net_amount' => 0,
                'is_paid' => false,
            ];
        }

        $periodStart = Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Check if already paid
        $isPaid = $staffMember->salaryPayments()->where('period', $period)->exists();

        // Calculate unjustified days
        $unjustifiedDays = $this->calculateUnjustifiedDays($staffMember, $periodStart, $periodEnd);

        // Get advances
        $advancesTotal = $staffMember->salaryAdvances()
            ->where('status', 'approved')
            ->sum('amount');

        // Get adjustments (pending + approved)
        $adjustments = $this->getAdjustmentTotals($staffMember, $period);

        // Auto-calculate commissions if none exist for the period
        $existingCommissions = $staffMember->commissionCalculations()->where('period', $period)->count();
        if ($existingCommissions === 0 && $staffMember->employeeCommissions()->where('is_active', true)->exists()) {
            $this->commissionService->calculateMonthlyCommissions($staffMember, $period);
        }

        // Get commissions (pending + approved)
        $commissionAmount = $staffMember->commissionCalculations()
            ->where('period', $period)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('commission_amount') ?? 0;

        // Calculate
        $dailyRate = round($baseSalary / 30, 2);
        $absenceDeduction = $unjustifiedDays * $dailyRate;

        $totalAdditions = $adjustments['overtime'] + $adjustments['bonus'] + $commissionAmount;
        $grossSalary = $baseSalary + $totalAdditions;

        $totalDeductions = $absenceDeduction + $advancesTotal + $adjustments['penalty'];
        $netAmount = $grossSalary - $totalDeductions;

        return [
            'base_salary' => $baseSalary,
            'currency' => $currency,
            'daily_rate' => $dailyRate,
            'unjustified_days' => $unjustifiedDays,
            'absence_deduction' => $absenceDeduction,
            'advances_total' => $advancesTotal,
            'overtime_amount' => $adjustments['overtime'],
            'bonus_amount' => $adjustments['bonus'],
            'penalty_amount' => $adjustments['penalty'],
            'commission_amount' => $commissionAmount,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'total_additions' => $totalAdditions,
            'net_amount' => $netAmount,
            'is_paid' => $isPaid,
        ];
    }

    private function calculateUnjustifiedDays(StaffMember $staffMember, Carbon $monthStart, Carbon $monthEnd): int
    {
        return $staffMember->leaves()
            ->where('type', 'unjustified')
            ->where('status', 'approved')
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                    ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                        $q2->where('start_date', '<=', $monthStart)
                            ->where('end_date', '>=', $monthEnd);
                    });
            })
            ->get()
            ->sum(function ($leave) use ($monthStart, $monthEnd) {
                $start = $leave->start_date->max($monthStart);
                $end = $leave->end_date->min($monthEnd);
                return $start->diffInDays($end) + 1;
            });
    }

    public function getAdjustmentTotals(StaffMember $staffMember, string $period): array
    {
        $adjustments = $staffMember->salaryAdjustments()
            ->where('period', $period)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        return [
            'overtime' => $adjustments->where('type', 'overtime')->sum('amount'),
            'bonus' => $adjustments->where('type', 'bonus')->sum('amount'),
            'penalty' => $adjustments->where('type', 'penalty')->sum('amount'),
            'other' => $adjustments->where('type', 'other')->sum('amount'),
            'total' => $adjustments->sum(fn($adj) => $adj->getSignedAmount()),
        ];
    }

    public function getOvertimeTotal(StaffMember $staffMember, string $period): float
    {
        return $staffMember->salaryAdjustments()
            ->where('period', $period)
            ->where('type', 'overtime')
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function getBonusTotal(StaffMember $staffMember, string $period): float
    {
        return $staffMember->salaryAdjustments()
            ->where('period', $period)
            ->where('type', 'bonus')
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function getPenaltyTotal(StaffMember $staffMember, string $period): float
    {
        return $staffMember->salaryAdjustments()
            ->where('period', $period)
            ->where('type', 'penalty')
            ->where('status', 'approved')
            ->sum('amount');
    }

    public function getCommissionTotal(StaffMember $staffMember, string $period): float
    {
        return $this->commissionService->getApprovedCommissionsForPeriod($staffMember, $period);
    }

    public function getPendingAdjustments(StaffMember $staffMember, ?string $period = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $staffMember->salaryAdjustments()->where('status', 'pending');

        if ($period) {
            $query->where('period', $period);
        }

        return $query->get();
    }
}
