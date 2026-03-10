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

        // Commission is based on PREVIOUS month's revenue
        // e.g., March payroll ($period = "2026-03") → February sales
        $commissionStart = Carbon::parse($period . '-01')->subMonth()->startOfMonth();
        $commissionEnd = Carbon::parse($period . '-01')->subMonth()->endOfMonth();
        $salesPeriod = $commissionStart->format('Y-m');

        foreach ($commissions as $commission) {
            // Check if commission was active during the SALES period (previous month)
            if (!$commission->isActiveForPeriod($salesPeriod)) {
                // Remove any stale pending calculation for this period
                CommissionCalculation::where([
                    'staff_member_id' => $staffMember->id,
                    'employee_commission_id' => $commission->id,
                    'period' => $period,
                ])->where('status', 'pending')->delete();
                continue;
            }

            $baseAmount = $this->getBaseAmount($commission, $commissionStart, $commissionEnd);
            $commissionAmount = round($baseAmount * ($commission->percentage / 100), 2);

            $existing = CommissionCalculation::where([
                'staff_member_id' => $staffMember->id,
                'employee_commission_id' => $commission->id,
                'period' => $period,
            ])->first();

            if ($commissionAmount > 0) {
                if ($existing) {
                    // Update amounts but preserve status (don't reset approved → pending)
                    $existing->update([
                        'base_amount' => $baseAmount,
                        'commission_amount' => $commissionAmount,
                    ]);
                    $calculation = $existing;
                } else {
                    $calculation = CommissionCalculation::create([
                        'staff_member_id' => $staffMember->id,
                        'employee_commission_id' => $commission->id,
                        'period' => $period,
                        'base_amount' => $baseAmount,
                        'commission_amount' => $commissionAmount,
                        'status' => 'pending',
                    ]);
                }

                $calculations[] = $calculation;
            } elseif ($existing && $existing->status === 'pending') {
                // Commission is now $0, remove stale pending record
                $existing->delete();
            }
        }

        return $calculations;
    }

    private function getBaseAmount(EmployeeCommission $commission, Carbon $start, Carbon $end): float
    {
        if ($commission->source_type === 'store_sales') {
            return $this->calculateStoreCommission($commission, $start, $end);
        }

        return $this->calculateResellerCommission($commission, $start, $end);
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
