@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">
            <i class="bi bi-calendar-x"></i> {{ __('messages.my_leaves.title') }}
        </h1>
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

    <div class="row">
        {{-- Request Form --}}
        <div class="col-md-4">
            {{-- Quota Balances --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pie-chart"></i> {{ __('messages.my_leaves.my_balances') }} ({{ now()->year }})</h5>
                </div>
                <div class="card-body">
                    @foreach(['vacation', 'sick', 'dayoff'] as $type)
                        @php
                            $balance = $quotaBalances[$type] ?? null;
                            $colors = ['vacation' => 'primary', 'sick' => 'warning', 'dayoff' => 'info'];
                            $color = $colors[$type];
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <span class="badge bg-{{ $color }}">{{ __('messages.staff.leave_types.' . $type) }}</span>
                            </span>
                            <span>
                                @if($balance)
                                    <strong class="text-success">{{ number_format($balance['remaining'], 1) }}</strong>
                                    <small class="text-muted">/ {{ $balance['annual_quota'] }} {{ __('messages.staff.days') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </span>
                        </div>
                        @if($balance && $balance['annual_quota'] > 0)
                            @php
                                $percentage = min(100, ($balance['used'] / $balance['annual_quota']) * 100);
                            @endphp
                            <div class="progress mb-3" style="height: 5px;">
                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $percentage }}%"></div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Request Form --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.my_leaves.new_request') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('my-leaves.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('messages.staff.leave_type') }} *</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="vacation">{{ __('messages.staff.leave_types.vacation') }}</option>
                                <option value="dayoff">{{ __('messages.staff.leave_types.dayoff') }}</option>
                                <option value="sick">{{ __('messages.staff.leave_types.sick') }}</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">{{ __('messages.staff.start_date') }} *</label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                       id="start_date" name="start_date" min="{{ date('Y-m-d') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">{{ __('messages.staff.end_date') }} *</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                       id="end_date" name="end_date" min="{{ date('Y-m-d') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="start_half_day" name="start_half_day" value="1">
                                    <label class="form-check-label" for="start_half_day">
                                        {{ __('messages.my_leaves.start_half_day') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="end_half_day" name="end_half_day" value="1">
                                    <label class="form-check-label" for="end_half_day">
                                        {{ __('messages.my_leaves.end_half_day') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">{{ __('messages.staff.reason') }}</label>
                            <textarea class="form-control @error('reason') is-invalid @enderror"
                                      id="reason" name="reason" rows="2"
                                      placeholder="{{ __('messages.my_leaves.reason_placeholder') }}"></textarea>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> {{ __('messages.my_leaves.submit_request') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- My Requests --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> {{ __('messages.my_leaves.my_requests') }}</h5>
                    @if($pendingCount > 0)
                        <span class="badge bg-warning text-dark">{{ $pendingCount }} {{ __('messages.my_leaves.pending') }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($leaves->isEmpty())
                        <p class="text-muted text-center">{{ __('messages.my_leaves.no_requests') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.staff.leave_type') }}</th>
                                        <th>{{ __('messages.staff.period') }}</th>
                                        <th class="text-center">{{ __('messages.staff.days') }}</th>
                                        <th>{{ __('messages.staff.status') }}</th>
                                        <th>{{ __('messages.my_leaves.submitted_at') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($leaves as $leave)
                                        <tr>
                                            <td>
                                                <span class="badge bg-{{ $leave->getTypeBadgeClass() }}">
                                                    {{ $leave->getTypeLabel() }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ $leave->start_date->format('d/m/Y') }}
                                                @if($leave->start_half_day)
                                                    <small class="text-muted">({{ __('messages.my_leaves.afternoon') }})</small>
                                                @endif
                                                @if(!$leave->start_date->eq($leave->end_date))
                                                    <br>
                                                    <i class="bi bi-arrow-right"></i>
                                                    {{ $leave->end_date->format('d/m/Y') }}
                                                    @if($leave->end_half_day)
                                                        <small class="text-muted">({{ __('messages.my_leaves.morning') }})</small>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">{{ $leave->getDaysCount() }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $leave->getStatusBadgeClass() }}">
                                                    {{ $leave->getStatusLabel() }}
                                                </span>
                                                @if($leave->approver && $leave->status !== 'pending')
                                                    <br><small class="text-muted">{{ $leave->approver->name }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <small>{{ $leave->created_at->format('d/m/Y H:i') }}</small>
                                            </td>
                                            <td>
                                                @if($leave->status === 'pending')
                                                    <form action="{{ route('my-leaves.cancel', $leave) }}" method="POST"
                                                          onsubmit="return confirm('{{ __('messages.my_leaves.confirm_cancel') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.btn.cancel') }}">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $leaves->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
});
</script>
@endsection
