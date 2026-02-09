<?php

namespace App\Services;

use App\Models\StaffMember;
use App\Models\Leave;
use App\Models\LeaveQuota;
use Carbon\Carbon;

class LeaveQuotaService
{
    // Default quotas per type
    private const DEFAULT_QUOTAS = [
        'vacation' => ['annual' => 18, 'monthly' => 1.5, 'carryover' => 0],
        'sick' => ['annual' => 5, 'monthly' => 0, 'carryover' => 0],
        'dayoff' => ['annual' => 0, 'monthly' => 0, 'carryover' => 0],
    ];

    public function initializeQuotasForEmployee(StaffMember $staffMember, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $quotas = [];

        foreach (self::DEFAULT_QUOTAS as $type => $defaults) {
            $quota = LeaveQuota::updateOrCreate(
                [
                    'staff_member_id' => $staffMember->id,
                    'type' => $type,
                    'year' => $year,
                ],
                [
                    'annual_quota' => $defaults['annual'],
                    'monthly_accrual' => $defaults['monthly'],
                    'carryover_days' => $defaults['carryover'],
                ]
            );
            $quotas[$type] = $quota;
        }

        return $quotas;
    }

    public function getQuotaBalances(StaffMember $staffMember, ?int $year = null): array
    {
        $year = $year ?? now()->year;

        // Ensure quotas exist
        $existingQuotas = $staffMember->leaveQuotas()->where('year', $year)->get();

        if ($existingQuotas->isEmpty()) {
            $this->initializeQuotasForEmployee($staffMember, $year);
            $existingQuotas = $staffMember->leaveQuotas()->where('year', $year)->get();
        }

        $balances = [];
        foreach ($existingQuotas as $quota) {
            $balances[$quota->type] = [
                'quota' => $quota,
                'annual_quota' => $quota->annual_quota,
                'monthly_accrual' => $quota->monthly_accrual,
                'carryover' => $quota->carryover_days,
                'accrued' => $quota->getAccruedDays(),
                'used' => $quota->getUsedDays(),
                'remaining' => $quota->getRemainingDays(),
            ];
        }

        return $balances;
    }

    public function canRequestLeave(StaffMember $staffMember, string $type, float $days, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $balances = $this->getQuotaBalances($staffMember, $year);

        // Unjustified absences don't use quotas
        if ($type === 'unjustified') {
            return [
                'allowed' => true,
                'remaining' => null,
                'message' => null,
            ];
        }

        if (!isset($balances[$type])) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'message' => __('messages.staff.quota_not_found'),
            ];
        }

        $remaining = $balances[$type]['remaining'];

        if ($days > $remaining) {
            return [
                'allowed' => false,
                'remaining' => $remaining,
                'message' => __('messages.staff.insufficient_quota', [
                    'requested' => $days,
                    'remaining' => $remaining,
                ]),
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $remaining - $days,
            'message' => null,
        ];
    }

    public function validateLeaveRequest(Leave $leave): array
    {
        $staffMember = $leave->staffMember;
        $type = $leave->type;
        $days = $leave->getDaysCount();
        $year = $leave->start_date->year;

        return $this->canRequestLeave($staffMember, $type, $days, $year);
    }

    public function updateQuota(StaffMember $staffMember, string $type, int $year, array $data): LeaveQuota
    {
        return LeaveQuota::updateOrCreate(
            [
                'staff_member_id' => $staffMember->id,
                'type' => $type,
                'year' => $year,
            ],
            $data
        );
    }

    public function carryOverQuotas(StaffMember $staffMember, int $fromYear, int $toYear): void
    {
        $previousQuotas = $staffMember->leaveQuotas()->where('year', $fromYear)->get();

        foreach ($previousQuotas as $quota) {
            $remaining = $quota->getRemainingDays();

            if ($remaining > 0 && $quota->type === 'vacation') {
                // Only carry over vacation days (max 5 days for example)
                $carryover = min($remaining, 5);

                $this->updateQuota($staffMember, $quota->type, $toYear, [
                    'annual_quota' => self::DEFAULT_QUOTAS[$quota->type]['annual'],
                    'monthly_accrual' => self::DEFAULT_QUOTAS[$quota->type]['monthly'],
                    'carryover_days' => $carryover,
                ]);
            }
        }
    }

    public function linkLeaveToQuota(Leave $leave): void
    {
        if ($leave->type === 'unjustified') {
            return;
        }

        $quota = LeaveQuota::where('staff_member_id', $leave->staff_member_id)
            ->where('type', $leave->type)
            ->where('year', $leave->start_date->year)
            ->first();

        if ($quota) {
            $leave->update(['leave_quota_id' => $quota->id]);
        }
    }
}
