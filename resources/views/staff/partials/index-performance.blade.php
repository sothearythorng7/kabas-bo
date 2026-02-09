{{-- Onglet Performances --}}

{{-- Filtres --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="performance">
            <div class="col-md-3">
                <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                <select class="form-select" id="store_id" name="store_id" onchange="this.form.submit()">
                    <option value="">{{ __('messages.staff.all_stores') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ $perfStoreId == $store->id ? 'selected' : '' }}>
                            {{ $store->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="period" class="form-label">{{ __('messages.staff.period') }}</label>
                <input type="month" class="form-control" id="period" name="period"
                       value="{{ $perfPeriod }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

{{-- Cartes résumé --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold">{{ $perfTotals['total_employees'] }}</div>
                <small>{{ __('messages.staff.employees') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold">{{ number_format($perfTotals['total_sales'], 0, ',', ' ') }} $</div>
                <small>{{ __('messages.staff.total_sales') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold">{{ $perfTotals['total_sales_count'] }}</div>
                <small>{{ __('messages.staff.transactions') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning h-100">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold">{{ number_format($perfTotals['avg_attendance'], 1) }}%</div>
                <small>{{ __('messages.staff.avg_attendance') }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Tableau principal --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-table"></i> {{ __('messages.staff.all_performances') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.staff.name') }}</th>
                                <th>{{ __('messages.staff.store') }}</th>
                                <th class="text-center">{{ __('messages.staff.shifts') }}</th>
                                <th class="text-end">{{ __('messages.staff.sales') }}</th>
                                <th class="text-center">{{ __('messages.staff.transactions') }}</th>
                                <th class="text-center">{{ __('messages.staff.attendance_rate') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($performances as $perf)
                                <tr>
                                    <td>
                                        <strong>{{ $perf['staff_member']->name }}</strong>
                                        @if($perf['staff_member']->user?->roles?->isNotEmpty())
                                            <br><small class="text-muted">{{ $perf['staff_member']->user->roles->pluck('name')->join(', ') }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $perf['staff_member']->store->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $perf['shifts_worked'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($perf['total_sales'], 2) }} $</strong>
                                    </td>
                                    <td class="text-center">{{ $perf['sales_count'] }}</td>
                                    <td class="text-center">
                                        @php
                                            $rate = $perf['attendance_rate'];
                                            $badgeClass = $rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger');
                                        @endphp
                                        <span class="badge bg-{{ $badgeClass }}">{{ $rate }}%</span>
                                        @if($perf['absences'] > 0)
                                            <br><small class="text-danger">{{ $perf['absences'] }} abs.</small>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('staff.show', ['staff_member' => $perf['staff_member'], 'tab' => 'performance', 'perf_period' => $perfPeriod]) }}"
                                           class="btn btn-sm btn-outline-primary" title="{{ __('messages.btn.view') }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        {{ __('messages.staff.no_staff') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($performances->isNotEmpty())
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3">{{ __('messages.staff.total') }}</th>
                                    <th class="text-end">{{ number_format($perfTotals['total_sales'], 2) }} $</th>
                                    <th class="text-center">{{ $perfTotals['total_sales_count'] }}</th>
                                    <th class="text-center">{{ number_format($perfTotals['avg_attendance'], 1) }}%</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Performers --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> {{ __('messages.staff.top_performers') }}</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($topPerformers as $index => $perf)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                @if($index === 0)
                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                @elseif($index === 1)
                                    <i class="bi bi-trophy-fill text-secondary me-1"></i>
                                @elseif($index === 2)
                                    <i class="bi bi-trophy-fill text-danger me-1"></i>
                                @else
                                    <span class="badge bg-light text-dark me-1">{{ $index + 1 }}</span>
                                @endif
                                <strong>{{ $perf['staff_member']->name }}</strong>
                                <br>
                                <small class="text-muted">{{ $perf['staff_member']->store->name ?? '-' }}</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success">{{ number_format($perf['total_sales'], 0) }} $</strong>
                                <br>
                                <small class="text-muted">{{ $perf['sales_count'] }} ventes</small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted text-center">{{ __('messages.staff.no_data') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Stats rapides --}}
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> {{ __('messages.staff.quick_stats') }}</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @if($perfTotals['total_sales_count'] > 0)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('messages.staff.avg_ticket') }}</span>
                            <strong>{{ number_format($perfTotals['total_sales'] / $perfTotals['total_sales_count'], 2) }} $</strong>
                        </li>
                    @endif
                    @if($perfTotals['total_employees'] > 0)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('messages.staff.avg_sales_per_employee') }}</span>
                            <strong>{{ number_format($perfTotals['total_sales'] / $perfTotals['total_employees'], 2) }} $</strong>
                        </li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ __('messages.staff.total_absences') }}</span>
                        <strong class="text-danger">{{ $perfTotals['total_absences'] }} {{ __('messages.staff.days') }}</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
