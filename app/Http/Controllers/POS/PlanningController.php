<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Leave;
use App\Models\Store;
use App\Models\StaffMember;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PlanningController extends Controller
{
    protected LeaveQuotaService $leaveQuotaService;

    public function __construct(LeaveQuotaService $leaveQuotaService)
    {
        $this->leaveQuotaService = $leaveQuotaService;
    }

    public function getAbsences(Request $request, $storeId)
    {
        $start = Carbon::parse($request->get('start', now()->startOfMonth()));
        $end = Carbon::parse($request->get('end', now()->endOfMonth()));

        $leaves = Leave::with('staffMember:id,name,user_id')
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            ->whereHas('staffMember', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->get();

        $absences = $leaves->map(function ($leave) {
            return [
                'id' => $leave->id,
                'user_id' => $leave->staffMember->user_id,
                'user_name' => $leave->staffMember->name,
                'type' => $leave->type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'status' => $leave->status,
                'reason' => $leave->reason,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $absences,
        ]);
    }

    public function getUserLeaveBalance($userId)
    {
        $staffMember = StaffMember::where('user_id', $userId)->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        $balances = $this->leaveQuotaService->getQuotaBalances($staffMember);

        $formattedBalances = collect($balances)->map(function ($balance, $type) {
            return [
                'type' => $type,
                'annual_quota' => $balance['annual_quota'],
                'accrued' => $balance['accrued'],
                'used' => $balance['used'],
                'remaining' => $balance['remaining'],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedBalances,
        ]);
    }

    public function requestLeave(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:vacation,dayoff,sick',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $staffMember = StaffMember::where('user_id', $validated['user_id'])->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found for this user',
            ], 404);
        }

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        // Check quota
        $canRequest = $this->leaveQuotaService->canRequestLeave(
            $staffMember,
            $validated['type'],
            $days,
            $startDate->year
        );

        if (!$canRequest['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $canRequest['message'],
                'remaining_days' => $canRequest['remaining'],
            ], 400);
        }

        // Create leave request
        $leave = $staffMember->leaves()->create([
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.staff.leave_created'),
            'data' => [
                'id' => $leave->id,
                'type' => $leave->type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'status' => $leave->status,
                'remaining_days' => $canRequest['remaining'],
            ],
        ]);
    }

    public function getStoreStaff($storeId)
    {
        $staff = StaffMember::where('store_id', $storeId)
            ->where('contract_status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'phone' => $member->phone,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function getTodayAbsences($storeId)
    {
        $today = now()->format('Y-m-d');

        $leaves = Leave::with('staffMember:id,name,user_id')
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereHas('staffMember', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->where('contract_status', 'active');
            })
            ->get();

        $absences = $leaves->map(function ($leave) {
            return [
                'user_id' => $leave->staffMember->user_id,
                'user_name' => $leave->staffMember->name,
                'type' => $leave->type,
                'reason' => $leave->reason,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $absences,
        ]);
    }

    /**
     * Get user's monthly planning (calendar with schedules and leaves)
     */
    public function getUserPlanning(Request $request, $userId)
    {
        $staffMember = StaffMember::with('schedules')->where('user_id', $userId)->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // Get staff member schedules
        $schedules = $staffMember->schedules;
        $scheduleDays = $schedules->pluck('day_of_week')->toArray();

        // Get staff member leaves for this month
        $leaves = Leave::where('staff_member_id', $staffMember->id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('start_date', [$monthStart, $monthEnd])
                    ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                    ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                        $q2->where('start_date', '<=', $monthStart)
                            ->where('end_date', '>=', $monthEnd);
                    });
            })
            ->get();

        // Build calendar days
        $days = [];
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalOff = 0;

        for ($date = $monthStart->copy(); $date <= $monthEnd; $date->addDay()) {
            $dayNum = $date->day;
            $dayOfWeek = $date->dayOfWeekIso; // 1=Monday, 7=Sunday
            $isScheduled = in_array($dayOfWeek, $scheduleDays);

            // Check if on leave
            $dayLeave = $leaves->first(function ($leave) use ($date) {
                return $date->between($leave->start_date, $leave->end_date);
            });

            $status = null;
            $type = null;
            $leaveStatus = null;

            if ($dayLeave) {
                $status = 'absent';
                $type = $dayLeave->type;
                $leaveStatus = $dayLeave->status;
                $totalAbsent++;
            } elseif ($isScheduled) {
                $status = 'present';
                $totalPresent++;
            } else {
                $status = 'off';
                $totalOff++;
            }

            $days[$dayNum] = [
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $dayOfWeek,
                'status' => $status,
                'type' => $type,
                'leave_status' => $leaveStatus,
            ];
        }

        // Format leaves for the list
        $leavesList = $leaves->map(function ($leave) {
            return [
                'id' => $leave->id,
                'type' => $leave->type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'status' => $leave->status,
                'reason' => $leave->reason,
            ];
        });

        // Get quota balances
        $balances = $this->leaveQuotaService->getQuotaBalances($staffMember);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $userId,
                    'name' => $staffMember->name,
                ],
                'month' => $month,
                'days' => $days,
                'totals' => [
                    'present' => $totalPresent,
                    'absent' => $totalAbsent,
                    'off' => $totalOff,
                ],
                'leaves' => $leavesList,
                'balances' => collect($balances)->map(function ($balance, $type) {
                    return [
                        'type' => $type,
                        'annual_quota' => $balance['annual_quota'],
                        'accrued' => $balance['accrued'],
                        'used' => $balance['used'],
                        'remaining' => $balance['remaining'],
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Get user's pending leave requests
     */
    public function getUserLeaves($userId)
    {
        $staffMember = StaffMember::where('user_id', $userId)->first();

        if (!$staffMember) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found',
            ], 404);
        }

        $leaves = Leave::where('staff_member_id', $staffMember->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $leavesList = $leaves->map(function ($leave) {
            return [
                'id' => $leave->id,
                'type' => $leave->type,
                'start_date' => $leave->start_date->format('Y-m-d'),
                'end_date' => $leave->end_date->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'status' => $leave->status,
                'reason' => $leave->reason,
                'created_at' => $leave->created_at->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $leavesList,
        ]);
    }
}
