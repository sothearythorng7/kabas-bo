@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">
            <i class="bi bi-calendar-check"></i> {{ __('messages.leave_requests.title') }}
            @if($pendingCount > 0)
                <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }} {{ __('messages.leave_requests.pending') }}</span>
            @endif
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('planning.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar3"></i> {{ __('messages.planning.calendar') }}
            </a>
            <a href="{{ route('planning.monthly') }}" class="btn btn-outline-success">
                <i class="bi bi-calendar-week"></i> {{ __('messages.planning.monthly_view') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="status" class="form-label">{{ __('messages.staff.status') }}</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ __('messages.staff.leave_status.pending') }}</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>{{ __('messages.staff.leave_status.approved') }}</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>{{ __('messages.staff.leave_status.rejected') }}</option>
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('messages.leave_requests.all_status') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                    <select class="form-select" id="store_id" name="store_id" onchange="this.form.submit()">
                        <option value="">{{ __('messages.staff.all_stores') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">{{ __('messages.staff.leave_type') }}</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="">{{ __('messages.leave_requests.all_types') }}</option>
                        <option value="vacation" {{ request('type') === 'vacation' ? 'selected' : '' }}>{{ __('messages.staff.leave_types.vacation') }}</option>
                        <option value="sick" {{ request('type') === 'sick' ? 'selected' : '' }}>{{ __('messages.staff.leave_types.sick') }}</option>
                        <option value="dayoff" {{ request('type') === 'dayoff' ? 'selected' : '' }}>{{ __('messages.staff.leave_types.dayoff') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">{{ __('messages.leave_requests.date_from') }}</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">{{ __('messages.leave_requests.date_to') }}</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> {{ __('messages.search') }}
                    </button>
                    <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Bulk Actions (only for pending) --}}
    @if($status === 'pending' && $leaves->count() > 0)
        <form id="bulkForm" action="{{ route('leave-requests.bulk-approve') }}" method="POST">
            @csrf
            <div class="card mb-3">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">{{ __('messages.staff.select_all') }}</label>
                    </div>
                    <span id="selectedCount" class="badge bg-secondary">0 {{ __('messages.leave_requests.selected') }}</span>
                    <div class="ms-auto">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" id="bulkApproveBtn" disabled>
                            <i class="bi bi-check-all"></i> {{ __('messages.leave_requests.approve_selected') }}
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" id="bulkRejectBtn" disabled>
                            <i class="bi bi-x-lg"></i> {{ __('messages.leave_requests.reject_selected') }}
                        </button>
                    </div>
                </div>
            </div>
    @endif

    {{-- Requests Table --}}
    <div class="card">
        <div class="card-body">
            @if($leaves->isEmpty())
                <p class="text-muted text-center py-4">{{ __('messages.leave_requests.no_requests') }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                @if($status === 'pending')
                                    <th style="width: 40px;"></th>
                                @endif
                                <th>{{ __('messages.staff.name') }}</th>
                                <th>{{ __('messages.staff.store') }}</th>
                                <th>{{ __('messages.staff.leave_type') }}</th>
                                <th>{{ __('messages.staff.period') }}</th>
                                <th class="text-center">{{ __('messages.staff.days') }}</th>
                                <th>{{ __('messages.staff.reason') }}</th>
                                <th>{{ __('messages.staff.status') }}</th>
                                <th>{{ __('messages.leave_requests.requested_at') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaves as $leave)
                                <tr>
                                    @if($status === 'pending')
                                        <td>
                                            <input type="checkbox" class="form-check-input leave-checkbox"
                                                   name="leave_ids[]" value="{{ $leave->id }}">
                                        </td>
                                    @endif
                                    <td>
                                        <a href="{{ route('staff.show', ['staffMember' => $leave->staffMember, 'tab' => 'leaves']) }}">
                                            <strong>{{ $leave->staffMember->name }}</strong>
                                        </a>
                                    </td>
                                    <td>{{ $leave->staffMember->store?->name ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $leave->getTypeBadgeClass() }}">
                                            {{ $leave->getTypeLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $leave->start_date->format('d/m/Y') }}
                                        @if($leave->start_half_day)
                                            <small class="text-muted">(PM)</small>
                                        @endif
                                        @if(!$leave->start_date->eq($leave->end_date))
                                            - {{ $leave->end_date->format('d/m/Y') }}
                                            @if($leave->end_half_day)
                                                <small class="text-muted">(AM)</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $leave->getDaysCount() }}</span>
                                    </td>
                                    <td>
                                        @if($leave->reason)
                                            <span title="{{ $leave->reason }}">{{ Str::limit($leave->reason, 30) }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $leave->getStatusBadgeClass() }}">
                                            {{ $leave->getStatusLabel() }}
                                        </span>
                                        @if($leave->approver)
                                            <br><small class="text-muted">{{ $leave->approver->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $leave->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-nowrap">
                                        @if($leave->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal" data-bs-target="#approveModal{{ $leave->id }}"
                                                    title="{{ __('messages.btn.approve') }}">
                                                <i class="bi bi-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}"
                                                    title="{{ __('messages.btn.reject') }}">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                @if($leave->status === 'pending')
                                    {{-- Approve Modal --}}
                                    <div class="modal fade" id="approveModal{{ $leave->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('leave-requests.approve', $leave) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title">{{ __('messages.leave_requests.approve_request') }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>{{ __('messages.leave_requests.confirm_approve', ['name' => $leave->staffMember->name]) }}</p>
                                                        <ul>
                                                            <li><strong>{{ __('messages.staff.leave_type') }}:</strong> {{ $leave->getTypeLabel() }}</li>
                                                            <li><strong>{{ __('messages.staff.period') }}:</strong> {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}</li>
                                                            <li><strong>{{ __('messages.staff.days') }}:</strong> {{ $leave->getDaysCount() }}</li>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                        <button type="submit" class="btn btn-success">
                                                            <i class="bi bi-check"></i> {{ __('messages.btn.approve') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Reject Modal --}}
                                    <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="{{ route('leave-requests.approve', $leave) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">{{ __('messages.leave_requests.reject_request') }}</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>{{ __('messages.leave_requests.confirm_reject', ['name' => $leave->staffMember->name]) }}</p>
                                                        <div class="mb-3">
                                                            <label for="rejection_reason{{ $leave->id }}" class="form-label">{{ __('messages.leave_requests.rejection_reason') }} *</label>
                                                            <textarea class="form-control" id="rejection_reason{{ $leave->id }}" name="rejection_reason"
                                                                      rows="3" required placeholder="{{ __('messages.leave_requests.rejection_reason_placeholder') }}"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-x"></i> {{ __('messages.btn.reject') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $leaves->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    @if($status === 'pending' && $leaves->count() > 0)
        </form>
    @endif
</div>

@if($status === 'pending')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.leave-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkRejectBtn = document.getElementById('bulkRejectBtn');

    function updateCount() {
        const checked = document.querySelectorAll('.leave-checkbox:checked').length;
        selectedCount.textContent = checked + ' {{ __("messages.leave_requests.selected") }}';
        bulkApproveBtn.disabled = checked === 0;
        bulkRejectBtn.disabled = checked === 0;
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateCount();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
});
</script>
@endif
@endsection
