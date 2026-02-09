<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class MyPlanningController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $staffMember = $user->staffMember;

        if (!$staffMember) {
            return redirect()->route('dashboard')
                ->with('error', __('messages.my_planning.no_staff_profile'));
        }

        $month = $request->get('month', now()->format('Y-m'));

        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $daysInMonth = $monthStart->daysInMonth;

        // Load staff member schedules and leaves
        $staffMember->load(['schedules', 'leaves' => function ($q) use ($monthStart, $monthEnd) {
            $q->whereIn('status', ['approved', 'pending'])
                ->where(function ($q2) use ($monthStart, $monthEnd) {
                    $q2->whereBetween('start_date', [$monthStart, $monthEnd])
                        ->orWhereBetween('end_date', [$monthStart, $monthEnd])
                        ->orWhere(function ($q3) use ($monthStart, $monthEnd) {
                            $q3->where('start_date', '<=', $monthStart)
                                ->where('end_date', '>=', $monthEnd);
                        });
                });
        }]);

        // Get working days from schedule
        $workingDays = $staffMember->schedules->where('is_working_day', true)->pluck('day_of_week')->toArray();

        // Build days array
        $days = [];
        $totalPresent = 0;
        $totalAbsent = 0;
        $totalOff = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $monthStart->copy()->day($i);
            $dateStr = $date->format('Y-m-d');
            $dayOfWeek = $date->dayOfWeek;

            // Check if staff member has leave on this day
            $leave = $staffMember->leaves->first(function ($l) use ($dateStr) {
                return $l->start_date->format('Y-m-d') <= $dateStr && $l->end_date->format('Y-m-d') >= $dateStr;
            });

            if ($leave) {
                $days[$i] = [
                    'status' => 'absent',
                    'type' => $leave->type,
                    'leave_status' => $leave->status,
                    'reason' => $leave->reason,
                ];
                $totalAbsent++;
            } elseif (in_array($dayOfWeek, $workingDays)) {
                $days[$i] = [
                    'status' => 'present',
                    'type' => null,
                ];
                $totalPresent++;
            } else {
                $days[$i] = [
                    'status' => 'off',
                    'type' => null,
                ];
                $totalOff++;
            }
        }

        // Get leaves for the detail table
        $leaves = $staffMember->leaves;

        return view('my-planning.index', compact(
            'user',
            'staffMember',
            'month',
            'monthStart',
            'days',
            'leaves',
            'totalPresent',
            'totalAbsent',
            'totalOff'
        ));
    }
}
