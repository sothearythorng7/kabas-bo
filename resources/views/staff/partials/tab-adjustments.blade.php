<div class="row">
    {{-- Add Adjustment Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.staff.add_adjustment') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.adjustments.store', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="adj_period" class="form-label">{{ __('messages.staff.period') }} *</label>
                        <input type="month" class="form-control" id="adj_period" name="period"
                               value="{{ now()->format('Y-m') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="adj_type" class="form-label">{{ __('messages.staff.adjustment_type') }} *</label>
                        <select class="form-select" id="adj_type" name="type" required onchange="toggleOvertimeFields()">
                            <option value="overtime">{{ __('messages.staff.adjustment_types.overtime') }}</option>
                            <option value="bonus">{{ __('messages.staff.adjustment_types.bonus') }}</option>
                            <option value="penalty">{{ __('messages.staff.adjustment_types.penalty') }}</option>
                            <option value="other">{{ __('messages.staff.adjustment_types.other') }}</option>
                        </select>
                    </div>

                    {{-- Overtime-specific fields --}}
                    <div id="overtime_fields">
                        <div class="mb-3">
                            <label for="adj_hours" class="form-label">{{ __('messages.staff.hours') }}</label>
                            <input type="number" class="form-control" id="adj_hours" name="hours"
                                   step="0.5" min="0" onchange="calculateOvertimeAmount()">
                        </div>
                        <div class="mb-3">
                            <label for="adj_hourly_rate" class="form-label">{{ __('messages.staff.hourly_rate') }}</label>
                            <input type="number" class="form-control" id="adj_hourly_rate" name="hourly_rate"
                                   step="0.00001" min="0" onchange="calculateOvertimeAmount()">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="adj_amount" class="form-label">{{ __('messages.staff.amount') }} *</label>
                        <input type="number" class="form-control" id="adj_amount" name="amount"
                               step="0.00001" min="0.01" required>
                        <small class="text-muted" id="amount_hint">{{ __('messages.staff.amount_hint') }}</small>
                    </div>

                    <div class="mb-3">
                        <label for="adj_description" class="form-label">{{ __('messages.staff.description') }}</label>
                        <textarea class="form-control" id="adj_description" name="description" rows="2"
                                  placeholder="{{ __('messages.staff.adjustment_description_placeholder') }}"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus"></i> {{ __('messages.staff.add_adjustment') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Adjustments List --}}
    <div class="col-md-8">
        {{-- Summary Cards --}}
        @php
            $currentPeriod = now()->format('Y-m');
            $periodAdjustments = $staffMember->salaryAdjustments->where('period', $currentPeriod);
            $approvedAdjustments = $periodAdjustments->where('status', 'approved');
        @endphp
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center py-3">
                        <div class="fs-5 fw-bold">
                            {{ number_format($approvedAdjustments->where('type', 'overtime')->sum('amount'), 2) }}
                        </div>
                        <small>{{ __('messages.staff.adjustment_types.overtime') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center py-3">
                        <div class="fs-5 fw-bold">
                            {{ number_format($approvedAdjustments->where('type', 'bonus')->sum('amount'), 2) }}
                        </div>
                        <small>{{ __('messages.staff.adjustment_types.bonus') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center py-3">
                        <div class="fs-5 fw-bold">
                            {{ number_format($approvedAdjustments->where('type', 'penalty')->sum('amount'), 2) }}
                        </div>
                        <small>{{ __('messages.staff.adjustment_types.penalty') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center py-3">
                        @php
                            $netTotal = $approvedAdjustments->sum(fn($a) => $a->getSignedAmount());
                        @endphp
                        <div class="fs-5 fw-bold">
                            {{ $netTotal >= 0 ? '+' : '' }}{{ number_format($netTotal, 2) }}
                        </div>
                        <small>{{ __('messages.staff.net_adjustments') }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Adjustments Table --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> {{ __('messages.staff.adjustments_history') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->salaryAdjustments->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_adjustments') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.period') }}</th>
                                    <th>{{ __('messages.staff.adjustment_type') }}</th>
                                    <th class="text-end">{{ __('messages.staff.amount') }}</th>
                                    <th>{{ __('messages.staff.description') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->salaryAdjustments->sortByDesc('created_at') as $adjustment)
                                    <tr>
                                        <td><strong>{{ $adjustment->period_label }}</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->getTypeBadgeClass() }}">
                                                {{ $adjustment->getTypeLabel() }}
                                            </span>
                                            @if($adjustment->type === 'overtime' && $adjustment->hours)
                                                <br><small class="text-muted">
                                                    {{ $adjustment->hours }}h x {{ number_format($adjustment->hourly_rate, 2) }}
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <strong class="{{ $adjustment->isDeduction() ? 'text-danger' : 'text-success' }}">
                                                {{ $adjustment->isDeduction() ? '-' : '+' }}{{ number_format($adjustment->amount, 2) }}
                                            </strong>
                                        </td>
                                        <td>{{ Str::limit($adjustment->description, 40) ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $adjustment->getStatusBadgeClass() }}">
                                                {{ $adjustment->getStatusLabel() }}
                                            </span>
                                            @if($adjustment->approver)
                                                <br><small class="text-muted">{{ $adjustment->approver->name }}</small>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if($adjustment->status === 'pending')
                                                <form action="{{ route('staff.adjustments.approve', $adjustment) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" title="{{ __('messages.btn.approve') }}">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('staff.adjustments.approve', $adjustment) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('messages.btn.reject') }}">
                                                        <i class="bi bi-x"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('staff.adjustments.delete', $adjustment) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('{{ __('messages.staff.confirm_delete_adjustment') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.btn.delete') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
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

<script>
function toggleOvertimeFields() {
    const type = document.getElementById('adj_type').value;
    const overtimeFields = document.getElementById('overtime_fields');
    const amountHint = document.getElementById('amount_hint');

    if (type === 'overtime') {
        overtimeFields.style.display = 'block';
        amountHint.textContent = '{{ __("messages.staff.overtime_amount_hint") }}';
    } else {
        overtimeFields.style.display = 'none';
        amountHint.textContent = '{{ __("messages.staff.amount_hint") }}';
    }

    if (type === 'penalty') {
        amountHint.textContent = '{{ __("messages.staff.penalty_amount_hint") }}';
    }
}

function calculateOvertimeAmount() {
    const hours = parseFloat(document.getElementById('adj_hours').value) || 0;
    const rate = parseFloat(document.getElementById('adj_hourly_rate').value) || 0;

    if (hours > 0 && rate > 0) {
        document.getElementById('adj_amount').value = (hours * rate).toFixed(5);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleOvertimeFields);
</script>
