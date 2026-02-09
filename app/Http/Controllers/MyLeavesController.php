<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;

class MyLeavesController extends Controller
{
    protected LeaveQuotaService $leaveQuotaService;

    public function __construct(LeaveQuotaService $leaveQuotaService)
    {
        $this->leaveQuotaService = $leaveQuotaService;
    }

    public function index()
    {
        $user = auth()->user();
        $staffMember = $user->staffMember;

        if (!$staffMember) {
            return redirect()->route('dashboard')
                ->with('error', __('messages.my_leaves.no_staff_profile'));
        }

        // Get quota balances
        $quotaBalances = $this->leaveQuotaService->getQuotaBalances($staffMember);

        // Get all leaves for this staff member
        $leaves = $staffMember->leaves()->orderByDesc('created_at')->paginate(15);

        // Count pending
        $pendingCount = $staffMember->leaves()->where('status', 'pending')->count();

        return view('my-leaves.index', compact('quotaBalances', 'leaves', 'pendingCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:vacation,dayoff,sick',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_half_day' => 'boolean',
            'end_half_day' => 'boolean',
            'reason' => 'nullable|string|max:500',
        ]);

        $staffMember = auth()->user()->staffMember;

        if (!$staffMember) {
            return redirect()->route('dashboard')
                ->with('error', __('messages.my_leaves.no_staff_profile'));
        }

        // Calculate days
        $days = \Carbon\Carbon::parse($validated['start_date'])
            ->diffInDays(\Carbon\Carbon::parse($validated['end_date'])) + 1;

        if ($request->boolean('start_half_day')) {
            $days -= 0.5;
        }
        if ($request->boolean('end_half_day')) {
            $days -= 0.5;
        }

        // Check quota
        $canRequest = $this->leaveQuotaService->canRequestLeave(
            $staffMember,
            $validated['type'],
            $days,
            \Carbon\Carbon::parse($validated['start_date'])->year
        );

        if (!$canRequest['allowed']) {
            return redirect()
                ->route('my-leaves.index')
                ->with('error', $canRequest['message']);
        }

        // Create leave request
        $staffMember->leaves()->create([
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'start_half_day' => $request->boolean('start_half_day'),
            'end_half_day' => $request->boolean('end_half_day'),
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('my-leaves.index')
            ->with('success', __('messages.my_leaves.request_submitted'));
    }

    public function cancel(Leave $leave)
    {
        // Only allow canceling own pending requests
        $staffMember = auth()->user()->staffMember;
        if (!$staffMember || $leave->staff_member_id !== $staffMember->id) {
            abort(403);
        }

        if ($leave->status !== 'pending') {
            return redirect()
                ->route('my-leaves.index')
                ->with('error', __('messages.my_leaves.cannot_cancel'));
        }

        $leave->delete();

        return redirect()
            ->route('my-leaves.index')
            ->with('success', __('messages.my_leaves.request_cancelled'));
    }
}
