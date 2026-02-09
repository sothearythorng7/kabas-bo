<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\Store;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;

class LeaveRequestsController extends Controller
{
    protected LeaveQuotaService $leaveQuotaService;

    public function __construct(LeaveQuotaService $leaveQuotaService)
    {
        $this->leaveQuotaService = $leaveQuotaService;
    }

    public function index(Request $request)
    {
        $query = Leave::with(['staffMember.store', 'approver'])
            ->orderByDesc('created_at');

        // Status filter (default: pending)
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Store filter
        if ($request->filled('store_id')) {
            $query->whereHas('staffMember', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('end_date', '<=', $request->date_to);
        }

        $leaves = $query->paginate(20);
        $stores = Store::orderBy('name')->get();

        // Count pending requests
        $pendingCount = Leave::where('status', 'pending')->count();

        return view('leave-requests.index', compact('leaves', 'stores', 'status', 'pendingCount'));
    }

    public function approve(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
        ]);

        if ($validated['action'] === 'approve') {
            // Validate quota before approving
            $validation = $this->leaveQuotaService->validateLeaveRequest($leave);

            if (!$validation['allowed']) {
                return redirect()
                    ->route('leave-requests.index')
                    ->with('error', $validation['message']);
            }

            $leave->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Link leave to quota
            $this->leaveQuotaService->linkLeaveToQuota($leave);

            $message = __('messages.leave_requests.approved');
        } else {
            $leave->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'reason' => $leave->reason . ($validated['rejection_reason'] ? "\n[Rejet: " . $validated['rejection_reason'] . "]" : ''),
            ]);

            $message = __('messages.leave_requests.rejected');
        }

        return redirect()
            ->route('leave-requests.index')
            ->with('success', $message);
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'leave_ids' => 'required|array|min:1',
            'leave_ids.*' => 'exists:leaves,id',
            'action' => 'required|in:approve,reject',
        ]);

        $count = 0;
        $errors = [];

        foreach ($validated['leave_ids'] as $leaveId) {
            $leave = Leave::find($leaveId);

            if ($leave->status !== 'pending') {
                continue;
            }

            if ($validated['action'] === 'approve') {
                $validation = $this->leaveQuotaService->validateLeaveRequest($leave);

                if (!$validation['allowed']) {
                    $errors[] = $leave->staffMember->name . ': ' . $validation['message'];
                    continue;
                }

                $leave->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                $this->leaveQuotaService->linkLeaveToQuota($leave);
            } else {
                $leave->update([
                    'status' => 'rejected',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            $count++;
        }

        $message = __('messages.leave_requests.bulk_processed', ['count' => $count]);

        if (!empty($errors)) {
            $message .= ' ' . __('messages.leave_requests.bulk_errors', ['count' => count($errors)]);
        }

        return redirect()
            ->route('leave-requests.index')
            ->with($errors ? 'warning' : 'success', $message);
    }
}
