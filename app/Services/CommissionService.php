<?php

namespace App\Services;

use App\Models\StaffMember;
use App\Models\Sale;
use App\Models\ResellerSalesReport;
use App\Models\EmployeeCommission;
use App\Models\CommissionCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function calculateMonthlyCommissions(StaffMember $staffMember, string $period): array
    {
        $calculations = [];
        $commissions = $staffMember->employeeCommissions()->active()->get();

        foreach ($commissions as $commission) {
            if (!$commission->isActiveForPeriod($period)) {
                continue;
            }

            $baseAmount = $this->getBaseAmount($commission, $period);
            $commissionAmount = round($baseAmount * ($commission->percentage / 100), 2);

            if ($commissionAmount > 0) {
                $calculation = CommissionCalculation::updateOrCreate(
                    [
                        'staff_member_id' => $staffMember->id,
                        'employee_commission_id' => $commission->id,
                        'period' => $period,
                    ],
                    [
                        'base_amount' => $baseAmount,
                        'commission_amount' => $commissionAmount,
                        'status' => 'pending',
                    ]
                );

                $calculations[] = $calculation;
            }
        }

        return $calculations;
    }

    private function getBaseAmount(EmployeeCommission $commission, string $period): float
    {
        $periodStart = Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        if ($commission->source_type === 'store_sales') {
            return $this->calculateStoreCommission($commission, $periodStart, $periodEnd);
        }

        return $this->calculateResellerCommission($commission, $periodStart, $periodEnd);
    }

    private function calculateStoreCommission(EmployeeCommission $commission, Carbon $start, Carbon $end): float
    {
        $query = Sale::whereBetween('created_at', [$start, $end]);

        if ($commission->source_id) {
            $query->where('store_id', $commission->source_id);
        }

        // Exclude voucher payments from commission calculation
        return Sale::sumRealRevenue($query->get());
    }

    private function calculateResellerCommission(EmployeeCommission $commission, Carbon $start, Carbon $end): float
    {
        $query = ResellerSalesReport::with('items')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
            });

        if ($commission->source_id) {
            $query->where('reseller_id', $commission->source_id);
        }

        return $query->get()->sum(fn($report) => $report->totalAmount());
    }

    public function approveCommissions(array $calculationIds, int $approvedBy): int
    {
        return CommissionCalculation::whereIn('id', $calculationIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);
    }

    public function markCommissionsAsPaid(array $calculationIds): int
    {
        return CommissionCalculation::whereIn('id', $calculationIds)
            ->where('status', 'approved')
            ->update(['status' => 'paid']);
    }

    public function getCommissionSummary(StaffMember $staffMember, string $period): array
    {
        $calculations = $staffMember->commissionCalculations()
            ->where('period', $period)
            ->with('employeeCommission')
            ->get();

        return [
            'total_base' => $calculations->sum('base_amount'),
            'total_commission' => $calculations->sum('commission_amount'),
            'pending' => $calculations->where('status', 'pending')->sum('commission_amount'),
            'approved' => $calculations->where('status', 'approved')->sum('commission_amount'),
            'paid' => $calculations->where('status', 'paid')->sum('commission_amount'),
            'details' => $calculations,
        ];
    }

    public function getApprovedCommissionsForPeriod(StaffMember $staffMember, string $period): float
    {
        return $staffMember->commissionCalculations()
            ->where('period', $period)
            ->where('status', 'approved')
            ->sum('commission_amount') ?? 0;
    }

    public function calculateAllEmployeesCommissions(string $period): array
    {
        $results = [];
        $staffMembers = StaffMember::where('contract_status', 'active')
            ->whereHas('employeeCommissions', function ($q) {
                $q->active();
            })
            ->get();

        foreach ($staffMembers as $staffMember) {
            $calculations = $this->calculateMonthlyCommissions($staffMember, $period);
            $results[$staffMember->id] = [
                'staffMember' => $staffMember,
                'calculations' => $calculations,
                'total' => collect($calculations)->sum('commission_amount'),
            ];
        }

        return $results;
    }
}
