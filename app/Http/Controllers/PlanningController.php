<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Leave;
use App\Models\Store;
use App\Models\StaffMember;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $stores = Store::orderBy('name')->get();
        $selectedStore = $request->get('store_id');

        return view('planning.index', compact('stores', 'selectedStore'));
    }

    public function apiEvents(Request $request)
    {
        $start = Carbon::parse($request->get('start', now()->startOfMonth()));
        $end = Carbon::parse($request->get('end', now()->endOfMonth()));
        $storeId = $request->get('store_id');

        $query = Leave::with('staffMember')
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            })
            // Only show leaves for active staff members
            ->whereHas('staffMember', function ($q) {
                $q->where('contract_status', 'active');
            });

        if ($storeId) {
            $query->whereHas('staffMember', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }

        $leaves = $query->get();

        $events = $leaves->map(function ($leave) {
            $colorMap = [
                'vacation' => '#0d6efd', // primary
                'sick' => '#ffc107', // warning
                'dayoff' => '#0dcaf0', // info
                'unjustified' => '#dc3545', // danger
            ];

            $statusClass = $leave->status === 'pending' ? ' (En attente)' : '';

            return [
                'id' => $leave->id,
                'title' => $leave->staffMember->name . ' - ' . $leave->getTypeLabel() . $statusClass,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar end is exclusive
                'color' => $colorMap[$leave->type] ?? '#6c757d',
                'className' => $leave->status === 'pending' ? 'opacity-50' : '',
                'extendedProps' => [
                    'staff_member_id' => $leave->staff_member_id,
                    'user_name' => $leave->staffMember->name,
                    'type' => $leave->type,
                    'type_label' => $leave->getTypeLabel(),
                    'status' => $leave->status,
                    'days' => $leave->getDaysCount(),
                    'reason' => $leave->reason,
                ],
            ];
        });

        return response()->json($events);
    }

    public function performance(Request $request)
    {
        // Redirect to staff index with performance tab
        return redirect()->route('staff.index', array_merge(['tab' => 'performance'], $request->all()));
    }

    public function monthly(Request $request)
    {
        $stores = Store::orderBy('name')->get();
        $storeId = $request->get('store_id');
        $month = $request->get('month', now()->format('Y-m'));

        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Build array of days
        $days = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $monthStart->copy()->day($i);
            $days[] = [
                'day' => $i,
                'date' => $date->format('Y-m-d'),
                'dow' => $date->dayOfWeek, // 0 = Sunday, 6 = Saturday
                'is_weekend' => $date->isWeekend(),
                'label' => $date->translatedFormat('D'),
            ];
        }

        // Get active staff members for selected store
        $query = StaffMember::with(['schedules', 'leaves' => function ($q) use ($monthStart, $monthEnd) {
            $q->whereIn('status', ['approved', 'pending'])
                ->where(function ($q2) use ($monthStart, $monthEnd) {
                    $q2->whereBetween('start_date', [$monthStart, $monthEnd])
                        ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                        ->orWhere(function ($q3) use ($monthStart, $monthEnd) {
                            $q3->where('start_date', '<=', $monthStart)
                                ->where('end_date', '>=', $monthEnd);
                        });
                });
        }])
            ->where('contract_status', 'active')
            ->orderBy('name');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $employees = $query->get();

        // Build planning grid
        $planning = [];
        foreach ($employees as $employee) {
            $employeeDays = [];

            // Get working days from schedule (day_of_week: 0=Sunday to 6=Saturday)
            $workingDays = $employee->schedules->where('is_working_day', true)->pluck('day_of_week')->toArray();

            foreach ($days as $day) {
                $date = $day['date'];
                $dayOfWeek = $day['dow'];

                // Check if employee has leave on this day
                $leave = $employee->leaves->first(function ($l) use ($date) {
                    return $l->start_date->format('Y-m-d') <= $date && $l->end_date->format('Y-m-d') >= $date;
                });

                if ($leave) {
                    $employeeDays[$day['day']] = [
                        'status' => 'absent',
                        'type' => $leave->type,
                        'leave_status' => $leave->status,
                        'reason' => $leave->reason,
                    ];
                } elseif (in_array($dayOfWeek, $workingDays)) {
                    $employeeDays[$day['day']] = [
                        'status' => 'present',
                        'type' => null,
                    ];
                } else {
                    $employeeDays[$day['day']] = [
                        'status' => 'off',
                        'type' => null,
                    ];
                }
            }

            $planning[] = [
                'employee' => $employee,
                'days' => $employeeDays,
                'total_present' => collect($employeeDays)->where('status', 'present')->count(),
                'total_absent' => collect($employeeDays)->where('status', 'absent')->count(),
            ];
        }

        // Summary stats
        $summary = [
            'total_employees' => count($planning),
            'days_in_month' => $daysInMonth,
        ];

        return view('planning.monthly', compact(
            'stores',
            'storeId',
            'month',
            'days',
            'planning',
            'summary',
            'monthStart'
        ));
    }
}
