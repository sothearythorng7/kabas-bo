<div class="row">
    {{-- Quota Balances --}}
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> {{ __('messages.staff.quota_balances') }} ({{ now()->year }})</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(['vacation', 'sick', 'dayoff'] as $type)
                        @php
                            $balance = $quotaBalances[$type] ?? null;
                            $colors = [
                                'vacation' => 'primary',
                                'sick' => 'warning',
                                'dayoff' => 'info',
                            ];
                            $color = $colors[$type];
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="card border-{{ $color }}">
                                <div class="card-header bg-{{ $color }} text-white">
                                    <strong>{{ __('messages.staff.leave_types.' . $type) }}</strong>
                                </div>
                                <div class="card-body">
                                    @if($balance)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('messages.staff.annual_quota') }}:</span>
                                            <strong>{{ $balance['annual_quota'] }} {{ __('messages.staff.days') }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('messages.staff.accrued') }}:</span>
                                            <strong>{{ number_format($balance['accrued'], 1) }} {{ __('messages.staff.days') }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ __('messages.staff.used') }}:</span>
                                            <strong class="text-danger">{{ number_format($balance['used'], 1) }} {{ __('messages.staff.days') }}</strong>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong>{{ __('messages.staff.remaining') }}:</strong></span>
                                            <strong class="text-success fs-5">{{ number_format($balance['remaining'], 1) }}</strong>
                                        </div>

                                        {{-- Progress bar --}}
                                        @php
                                            $percentage = $balance['annual_quota'] > 0
                                                ? min(100, ($balance['used'] / $balance['annual_quota']) * 100)
                                                : 0;
                                        @endphp
                                        <div class="progress mt-3" style="height: 10px;">
                                            <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                                 style="width: {{ $percentage }}%"
                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ number_format($percentage, 0) }}% {{ __('messages.staff.quota_used') }}</small>
                                    @else
                                        <p class="text-muted mb-0">{{ __('messages.staff.no_quota_defined') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(isset($quotaBalances['vacation']['carryover']) && $quotaBalances['vacation']['carryover'] > 0)
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        {{ __('messages.staff.carryover_info', ['days' => $quotaBalances['vacation']['carryover']]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Edit Quota Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> {{ __('messages.staff.edit_quota') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.quotas.store', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="quota_type" class="form-label">{{ __('messages.staff.leave_type') }} *</label>
                        <select class="form-select" id="quota_type" name="type" required>
                            <option value="vacation">{{ __('messages.staff.leave_types.vacation') }}</option>
                            <option value="sick">{{ __('messages.staff.leave_types.sick') }}</option>
                            <option value="dayoff">{{ __('messages.staff.leave_types.dayoff') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quota_year" class="form-label">{{ __('messages.staff.year') }} *</label>
                        <select class="form-select" id="quota_year" name="year" required>
                            @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="annual_quota" class="form-label">{{ __('messages.staff.annual_quota') }} *</label>
                        <input type="number" class="form-control" id="annual_quota" name="annual_quota"
                               step="0.5" min="0" max="365" value="18" required>
                        <small class="text-muted">{{ __('messages.staff.annual_quota_help') }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="monthly_accrual" class="form-label">{{ __('messages.staff.monthly_accrual') }} *</label>
                        <input type="number" class="form-control" id="monthly_accrual" name="monthly_accrual"
                               step="0.1" min="0" max="31" value="1.5" required>
                        <small class="text-muted">{{ __('messages.staff.monthly_accrual_help') }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="carryover_days" class="form-label">{{ __('messages.staff.carryover_days') }} *</label>
                        <input type="number" class="form-control" id="carryover_days" name="carryover_days"
                               step="0.5" min="0" max="365" value="0" required>
                        <small class="text-muted">{{ __('messages.staff.carryover_help') }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> {{ __('messages.btn.save') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Quota History --}}
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('messages.staff.quota_history') }}</h5>
    </div>
    <div class="card-body">
        @if($staffMember->leaveQuotas->isEmpty())
            <p class="text-muted text-center">{{ __('messages.staff.no_quota_history') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.staff.year') }}</th>
                            <th>{{ __('messages.staff.leave_type') }}</th>
                            <th class="text-center">{{ __('messages.staff.annual_quota') }}</th>
                            <th class="text-center">{{ __('messages.staff.monthly_accrual') }}</th>
                            <th class="text-center">{{ __('messages.staff.carryover_days') }}</th>
                            <th class="text-center">{{ __('messages.staff.accrued') }}</th>
                            <th class="text-center">{{ __('messages.staff.used') }}</th>
                            <th class="text-center">{{ __('messages.staff.remaining') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMember->leaveQuotas->sortByDesc('year') as $quota)
                            <tr>
                                <td><strong>{{ $quota->year }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $quota->getTypeBadgeClass() }}">
                                        {{ $quota->getTypeLabel() }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $quota->annual_quota }}</td>
                                <td class="text-center">{{ $quota->monthly_accrual }}</td>
                                <td class="text-center">{{ $quota->carryover_days }}</td>
                                <td class="text-center">{{ number_format($quota->getAccruedDays(), 1) }}</td>
                                <td class="text-center text-danger">{{ number_format($quota->getUsedDays(), 1) }}</td>
                                <td class="text-center">
                                    <strong class="text-success">{{ number_format($quota->getRemainingDays(), 1) }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
