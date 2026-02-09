{{-- Performance individuelle de l'employé --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-graph-up"></i> {{ __('messages.staff.performance_title') }}
        </h5>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="tab" value="performance">
            <input type="month" name="perf_period" class="form-control form-control-sm"
                   value="{{ $performancePeriod ?? now()->format('Y-m') }}"
                   onchange="this.form.submit()">
        </form>
    </div>
    <div class="card-body">
        @if(isset($performanceData))
            {{-- Résumé en cartes --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold">{{ $performanceData['shifts_worked'] }}</div>
                            <small>{{ __('messages.staff.shifts_worked') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold">{{ number_format($performanceData['total_sales'], 0, ',', ' ') }}</div>
                            <small>{{ __('messages.staff.total_sales') }} ($)</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold">{{ $performanceData['sales_count'] }}</div>
                            <small>{{ __('messages.staff.sales_count') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning h-100">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold">{{ $performanceData['absences'] }}</div>
                            <small>{{ __('messages.staff.absences') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Détails des shifts --}}
            @if($performanceData['shifts']->isNotEmpty())
                <h6 class="mb-3"><i class="bi bi-clock-history"></i> {{ __('messages.staff.shift_details') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.staff.date') }}</th>
                                <th>{{ __('messages.staff.store') }}</th>
                                <th class="text-center">{{ __('messages.staff.duration') }}</th>
                                <th class="text-end">{{ __('messages.staff.sales') }}</th>
                                <th class="text-center">{{ __('messages.staff.transactions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($performanceData['shifts'] as $shift)
                                <tr>
                                    <td>
                                        {{ $shift->started_at->format('d/m/Y') }}
                                        <br><small class="text-muted">{{ $shift->started_at->format('H:i') }} - {{ $shift->ended_at ? $shift->ended_at->format('H:i') : '...' }}</small>
                                    </td>
                                    <td>{{ $shift->store->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($shift->ended_at)
                                            @php
                                                $diff = $shift->started_at->diff($shift->ended_at);
                                                $hours = ($diff->days * 24) + $diff->h;
                                            @endphp
                                            {{ $hours }}h {{ str_pad($diff->i, 2, '0', STR_PAD_LEFT) }}m
                                        @else
                                            <span class="badge bg-warning">{{ __('messages.staff.in_progress') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong>{{ number_format($shift->sales->sum('total'), 2) }} $</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $shift->sales->count() }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3">{{ __('messages.staff.total') }}</th>
                                <th class="text-end">{{ number_format($performanceData['total_sales'], 2) }} $</th>
                                <th class="text-center">{{ $performanceData['sales_count'] }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> {{ __('messages.staff.no_shifts_period') }}
                </div>
            @endif

            {{-- Moyennes et ratios --}}
            @if($performanceData['shifts_worked'] > 0)
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-calculator"></i> {{ __('messages.staff.averages') }}</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.avg_sales_per_shift') }}</span>
                                        <strong>{{ number_format($performanceData['total_sales'] / $performanceData['shifts_worked'], 2) }} $</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.avg_transactions_per_shift') }}</span>
                                        <strong>{{ number_format($performanceData['sales_count'] / $performanceData['shifts_worked'], 1) }}</strong>
                                    </li>
                                    @if($performanceData['sales_count'] > 0)
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>{{ __('messages.staff.avg_ticket') }}</span>
                                            <strong>{{ number_format($performanceData['total_sales'] / $performanceData['sales_count'], 2) }} $</strong>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-pie-chart"></i> {{ __('messages.staff.attendance') }}</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.working_days_scheduled') }}</span>
                                        <strong>{{ $performanceData['working_days_scheduled'] }}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.shifts_worked') }}</span>
                                        <strong class="text-success">{{ $performanceData['shifts_worked'] }}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.absences') }}</span>
                                        <strong class="text-danger">{{ $performanceData['absences'] }}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('messages.staff.attendance_rate') }}</span>
                                        <strong class="{{ $performanceData['attendance_rate'] >= 90 ? 'text-success' : ($performanceData['attendance_rate'] >= 75 ? 'text-warning' : 'text-danger') }}">
                                            {{ $performanceData['attendance_rate'] }}%
                                        </strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> {{ __('messages.staff.no_performance_data') }}
            </div>
        @endif
    </div>
</div>
