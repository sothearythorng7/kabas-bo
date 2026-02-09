@php
    // Only show for users who can approve leaves (admin, manager, hr roles)
    $canApproveLeaves = Auth::check() && Auth::user()->hasAnyRole(['admin', 'ADMIN', 'manager', 'MANAGER', 'hr', 'HR']);
    $pendingLeavesCount = 0;

    if ($canApproveLeaves) {
        $pendingLeavesCount = \App\Models\Leave::where('status', 'pending')->count();
    }
@endphp

@if($canApproveLeaves && $pendingLeavesCount > 0)
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-calendar-x-fill fs-4 me-3"></i>
        <div class="flex-grow-1">
            <strong>{{ __('messages.leave_requests.pending_alert_title') }}</strong><br>
            <small>
                {{ trans_choice('messages.leave_requests.pending_alert_message', $pendingLeavesCount, ['count' => $pendingLeavesCount]) }}
            </small>
        </div>
        <a href="{{ route('leave-requests.index') }}" class="btn btn-warning btn-sm ms-3">
            <i class="bi bi-eye"></i> {{ __('messages.leave_requests.view_requests') }}
        </a>
        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
