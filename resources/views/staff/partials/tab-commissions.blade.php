<div class="row">
    {{-- Commission Config Form --}}
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> {{ __('messages.staff.add_commission') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.commissions.store', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="source_type" class="form-label">{{ __('messages.staff.commission_source') }} *</label>
                        <select class="form-select" id="source_type" name="source_type" required onchange="updateSourceOptions()">
                            <option value="store_sales">{{ __('messages.staff.store_sales') }}</option>
                            <option value="reseller_sales">{{ __('messages.staff.reseller_sales') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="source_id" class="form-label">{{ __('messages.staff.specific_source') }}</label>
                        <select class="form-select" id="source_id" name="source_id">
                            <option value="">{{ __('messages.staff.all_sources') }}</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" data-type="store_sales">{{ $store->name }}</option>
                            @endforeach
                            @foreach($resellers as $reseller)
                                <option value="{{ $reseller->id }}" data-type="reseller_sales" style="display: none;">{{ $reseller->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('messages.staff.source_help') }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="percentage" class="form-label">{{ __('messages.staff.percentage') }} *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="percentage" name="percentage"
                                   step="0.01" min="0.01" max="100" value="1" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="effective_from" class="form-label">{{ __('messages.staff.effective_from') }} *</label>
                        <input type="date" class="form-control" id="effective_from" name="effective_from"
                               value="{{ date('Y-m-01') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="effective_to" class="form-label">{{ __('messages.staff.effective_to') }}</label>
                        <input type="date" class="form-control" id="effective_to" name="effective_to">
                        <small class="text-muted">{{ __('messages.staff.leave_empty_no_end') }}</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus"></i> {{ __('messages.staff.add_commission') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Calculate Commissions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> {{ __('messages.staff.calculate_commissions') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.commissions.calculate', $staffMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="calc_period" class="form-label">{{ __('messages.staff.period') }} *</label>
                        <input type="month" class="form-control" id="calc_period" name="period"
                               value="{{ now()->format('Y-m') }}" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-calculator"></i> {{ __('messages.staff.calculate') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Active Commissions --}}
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> {{ __('messages.staff.active_commissions') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->employeeCommissions->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_commissions_configured') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.commission_source') }}</th>
                                    <th>{{ __('messages.staff.specific_source') }}</th>
                                    <th class="text-center">{{ __('messages.staff.percentage') }}</th>
                                    <th>{{ __('messages.staff.period') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->employeeCommissions as $commission)
                                    <tr class="{{ !$commission->is_active ? 'table-secondary' : '' }}">
                                        <td>
                                            <span class="badge bg-{{ $commission->getSourceTypeBadge() }}">
                                                {{ $commission->getSourceTypeLabel() }}
                                            </span>
                                        </td>
                                        <td>{{ $commission->getSourceName() }}</td>
                                        <td class="text-center"><strong>{{ $commission->percentage }}%</strong></td>
                                        <td>
                                            {{ $commission->effective_from->format('d/m/Y') }}
                                            @if($commission->effective_to)
                                                - {{ $commission->effective_to->format('d/m/Y') }}
                                            @else
                                                - <span class="text-muted">{{ __('messages.staff.no_end_date') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($commission->is_active)
                                                <span class="badge bg-success">{{ __('messages.active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('messages.inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <form action="{{ route('staff.commissions.toggle', $commission) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $commission->is_active ? 'warning' : 'success' }}"
                                                        title="{{ $commission->is_active ? __('messages.btn.disable') : __('messages.btn.enable') }}">
                                                    <i class="bi bi-{{ $commission->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('staff.commissions.delete', $commission) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('messages.staff.confirm_delete_commission') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Commission Calculations --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cash-stack"></i> {{ __('messages.staff.commission_calculations') }}</h5>
            </div>
            <div class="card-body">
                @php
                    $calculations = $staffMember->commissionCalculations()->with('employeeCommission')->orderByDesc('period')->limit(12)->get();
                @endphp
                @if($calculations->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_calculations') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.period') }}</th>
                                    <th>{{ __('messages.staff.commission_source') }}</th>
                                    <th class="text-end">{{ __('messages.staff.base_amount') }}</th>
                                    <th class="text-center">{{ __('messages.staff.rate') }}</th>
                                    <th class="text-end">{{ __('messages.staff.commission_amount') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calculations as $calc)
                                    <tr>
                                        <td><strong>{{ $calc->period_label }}</strong></td>
                                        <td>{{ $calc->employeeCommission->getSourceTypeLabel() }}</td>
                                        <td class="text-end">{{ number_format($calc->base_amount, 2) }}</td>
                                        <td class="text-center">{{ $calc->employeeCommission->percentage }}%</td>
                                        <td class="text-end"><strong class="text-success">{{ number_format($calc->commission_amount, 2) }}</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $calc->getStatusBadgeClass() }}">
                                                {{ $calc->getStatusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($calc->status === 'pending')
                                                <form action="{{ route('staff.commissions.approve', $calc) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success" title="{{ __('messages.btn.approve') }}">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('staff.commissions.approve', $calc) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('messages.btn.reject') }}">
                                                        <i class="bi bi-x"></i>
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
function updateSourceOptions() {
    const sourceType = document.getElementById('source_type').value;
    const sourceSelect = document.getElementById('source_id');
    const options = sourceSelect.querySelectorAll('option[data-type]');

    options.forEach(option => {
        if (option.dataset.type === sourceType) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
            option.selected = false;
        }
    });
}
</script>
