@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">
            <i class="bi bi-graph-up"></i> {{ __('messages.planning.performance_title') }}
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('planning.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-calendar3"></i> {{ __('messages.planning.calendar') }}
            </a>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="period" class="form-label">{{ __('messages.staff.period') }}</label>
                    <input type="month" class="form-control" id="period" name="period"
                           value="{{ $period }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-3">
                    <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                    <select class="form-select" id="store_id" name="store_id" onchange="this.form.submit()">
                        <option value="">{{ __('messages.staff.all_stores') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $totals['total_employees'] }}</div>
                    <div>{{ __('messages.planning.total_employees') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ $totals['total_absences'] }}</div>
                    <div>{{ __('messages.planning.total_absences') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ number_format($totals['total_sales'], 0) }}</div>
                    <div>{{ __('messages.planning.total_sales') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <div class="fs-2 fw-bold">{{ number_format($totals['avg_attendance'], 1) }}%</div>
                    <div>{{ __('messages.planning.avg_attendance') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Top Performers --}}
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> {{ __('messages.planning.top_performers') }}</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topPerformers as $index => $perf)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'dark') }} me-2">
                                        #{{ $index + 1 }}
                                    </span>
                                    <a href="{{ route('staff.show', $perf['staffMember']) }}">{{ $perf['staffMember']->name }}</a>
                                    <br>
                                    <small class="text-muted">{{ $perf['staffMember']->store?->name ?? '-' }}</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-success">{{ number_format($perf['total_sales'], 0) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $perf['shifts_worked'] }} {{ __('messages.planning.shifts') }}</small>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">
                                {{ __('messages.planning.no_data') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Performance Table --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-table"></i> {{ __('messages.planning.performance_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.name') }}</th>
                                    <th>{{ __('messages.staff.store') }}</th>
                                    <th class="text-center">{{ __('messages.planning.shifts') }}</th>
                                    <th class="text-center">{{ __('messages.planning.absences') }}</th>
                                    <th class="text-center">{{ __('messages.planning.attendance') }}</th>
                                    <th class="text-end">{{ __('messages.planning.sales') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($performances as $perf)
                                    <tr>
                                        <td>
                                            <a href="{{ route('staff.show', $perf['staffMember']) }}">
                                                {{ $perf['staffMember']->name }}
                                            </a>
                                        </td>
                                        <td>{{ $perf['staffMember']->store?->name ?? '-' }}</td>
                                        <td class="text-center">{{ $perf['shifts_worked'] }}</td>
                                        <td class="text-center">
                                            @if($perf['absences'] > 0)
                                                <span class="badge bg-warning text-dark">{{ $perf['absences'] }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $rate = $perf['attendance_rate'];
                                                $badgeClass = $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $badgeClass }}">{{ number_format($rate, 0) }}%</span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">{{ number_format($perf['total_sales'], 0) }}</strong>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            {{ __('messages.planning.no_data') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
