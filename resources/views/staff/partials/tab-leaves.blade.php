<div class="row">
    {{-- Leave Request Form --}}
    <div class="col-md-4">
        {{-- Leave Balances --}}
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.leave_balance') }} ({{ now()->year }})</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold text-primary">{{ $leaveBalances['vacation'] ?? 0 }}</div>
                        <small>{{ __('messages.staff.leave_types.vacation') }}</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-warning">{{ $leaveBalances['sick'] ?? 0 }}</div>
                        <small>{{ __('messages.staff.leave_types.sick') }}</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold text-info">{{ $leaveBalances['dayoff'] ?? 0 }}</div>
                        <small>{{ __('messages.staff.leave_types.dayoff') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Request Leave Form --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.request_leave') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.leaves.store', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="leave_type" class="form-label">{{ __('messages.staff.leave_type') }} *</label>
                        <select class="form-select" id="leave_type" name="type" required>
                            <option value="vacation">{{ __('messages.staff.leave_types.vacation') }}</option>
                            <option value="dayoff">{{ __('messages.staff.leave_types.dayoff') }}</option>
                            <option value="sick">{{ __('messages.staff.leave_types.sick') }}</option>
                            <option value="unjustified">{{ __('messages.staff.leave_types.unjustified') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">{{ __('messages.staff.start_date') }} *</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">{{ __('messages.staff.end_date') }} *</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="leave_reason" class="form-label">{{ __('messages.staff.reason') }}</label>
                        <textarea class="form-control" id="leave_reason" name="reason" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> {{ __('messages.staff.create_leave') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Leaves List --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.leaves_history') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->leaves->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_leaves') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.leave_type') }}</th>
                                    <th>{{ __('messages.staff.period') }}</th>
                                    <th class="text-center">{{ __('messages.staff.days') }}</th>
                                    <th>{{ __('messages.staff.reason') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->leaves as $leave)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $leave->getTypeBadgeClass() }}">
                                                {{ $leave->getTypeLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $leave->start_date->format('d/m/Y') }}
                                            @if($leave->start_date->ne($leave->end_date))
                                                - {{ $leave->end_date->format('d/m/Y') }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $leave->getDaysCount() }}</span>
                                        </td>
                                        <td>{{ Str::limit($leave->reason, 30) ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $leave->getStatusBadgeClass() }}">
                                                {{ $leave->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($leave->status === 'pending')
                                                <form action="{{ route('staff.leaves.approve', $leave) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" title="{{ __('messages.btn.approve') }}">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('staff.leaves.approve', $leave) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('messages.btn.reject') }}"
                                                            onclick="return confirm('{{ __('messages.staff.confirm_reject') }}')">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                            @else
                                                @if($leave->approver)
                                                    <small class="text-muted">{{ $leave->approver->name }}</small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
