{{-- Onglet Planning mensuel --}}

{{-- Filtres --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="planning">
            <div class="col-md-3">
                <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                <select class="form-select" id="store_id" name="store_id" onchange="this.form.submit()">
                    <option value="">{{ __('messages.staff.all_stores') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ $planningStoreId == $store->id ? 'selected' : '' }}>
                            {{ $store->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="month" class="form-label">{{ __('messages.planning.month') }}</label>
                <input type="month" class="form-control" id="month" name="month"
                       value="{{ $planningMonth }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-6">
                {{-- Légende --}}
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <span class="badge bg-success"><i class="bi bi-check"></i> {{ __('messages.planning.present') }}</span>
                    <span class="badge bg-primary"><i class="bi bi-airplane"></i> {{ __('messages.staff.leave_types.vacation') }}</span>
                    <span class="badge bg-warning text-dark"><i class="bi bi-bandaid"></i> {{ __('messages.staff.leave_types.sick') }}</span>
                    <span class="badge bg-info"><i class="bi bi-cup-hot"></i> {{ __('messages.staff.leave_types.dayoff') }}</span>
                    <span class="badge bg-danger"><i class="bi bi-x"></i> {{ __('messages.staff.leave_types.unjustified') }}</span>
                    <span class="badge bg-light text-muted border">{{ __('messages.planning.day_off') }}</span>
                </div>
            </div>
        </form>
    </div>
</div>

@if(empty($planning))
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> {{ __('messages.planning.no_employees') }}
    </div>
@else
    {{-- Grille Planning --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 planning-grid">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="sticky-col bg-light" style="min-width: 180px;">
                                {{ __('messages.staff.name') }}
                            </th>
                            @foreach($planningDays as $day)
                                <th class="text-center {{ $day['is_weekend'] ? 'bg-light' : '' }}" style="min-width: 36px;">
                                    <small class="d-block text-muted">{{ $day['label'] }}</small>
                                    <strong>{{ $day['day'] }}</strong>
                                </th>
                            @endforeach
                            <th class="text-center bg-light" style="min-width: 60px;">
                                <i class="bi bi-check-circle text-success"></i>
                            </th>
                            <th class="text-center bg-light" style="min-width: 60px;">
                                <i class="bi bi-x-circle text-danger"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($planning as $row)
                            <tr>
                                <td class="sticky-col bg-white">
                                    <a href="{{ route('staff.show', ['staffMember' => $row['employee']->id, 'tab' => 'leaves']) }}"
                                       class="text-decoration-none">
                                        <strong>{{ $row['employee']->name }}</strong>
                                    </a>
                                    @if($row['employee']->store)
                                        <br><small class="text-muted">{{ $row['employee']->store->name }}</small>
                                    @endif
                                </td>
                                @foreach($planningDays as $day)
                                    @php
                                        $cell = $row['days'][$day['day']];
                                        $classes = '';
                                        $icon = '';
                                        $title = '';

                                        if ($cell['status'] === 'present') {
                                            $classes = 'bg-success-subtle text-success';
                                            $icon = '<i class="bi bi-check"></i>';
                                            $title = __('messages.planning.present');
                                        } elseif ($cell['status'] === 'absent') {
                                            $typeColors = [
                                                'vacation' => 'bg-primary text-white',
                                                'sick' => 'bg-warning',
                                                'dayoff' => 'bg-info text-white',
                                                'unjustified' => 'bg-danger text-white',
                                            ];
                                            $typeIcons = [
                                                'vacation' => '<i class="bi bi-airplane"></i>',
                                                'sick' => '<i class="bi bi-bandaid"></i>',
                                                'dayoff' => '<i class="bi bi-cup-hot"></i>',
                                                'unjustified' => '<i class="bi bi-x"></i>',
                                            ];
                                            $classes = $typeColors[$cell['type']] ?? 'bg-secondary text-white';
                                            if ($cell['leave_status'] === 'pending') {
                                                $classes .= ' opacity-50';
                                            }
                                            $icon = $typeIcons[$cell['type']] ?? '<i class="bi bi-dash"></i>';
                                            $title = __('messages.staff.leave_types.' . $cell['type']);
                                            if ($cell['leave_status'] === 'pending') {
                                                $title .= ' (' . __('messages.staff.leave_status.pending') . ')';
                                            }
                                            if ($cell['reason']) {
                                                $title .= ' - ' . $cell['reason'];
                                            }
                                        } else {
                                            $classes = $day['is_weekend'] ? 'bg-light' : 'bg-secondary-subtle';
                                            $icon = '';
                                            $title = __('messages.planning.day_off');
                                        }
                                    @endphp
                                    <td class="text-center {{ $classes }}" title="{{ $title }}" style="cursor: help;">
                                        {!! $icon !!}
                                    </td>
                                @endforeach
                                <td class="text-center bg-light">
                                    <strong class="text-success">{{ $row['total_present'] }}</strong>
                                </td>
                                <td class="text-center bg-light">
                                    <strong class="text-danger">{{ $row['total_absent'] }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th class="sticky-col bg-light">
                                {{ __('messages.planning.daily_total') }}
                            </th>
                            @foreach($planningDays as $day)
                                @php
                                    $presentCount = collect($planning)->filter(fn($r) => $r['days'][$day['day']]['status'] === 'present')->count();
                                    $absentCount = collect($planning)->filter(fn($r) => $r['days'][$day['day']]['status'] === 'absent')->count();
                                @endphp
                                <td class="text-center {{ $day['is_weekend'] ? 'bg-light' : '' }}">
                                    <small>
                                        <span class="text-success">{{ $presentCount }}</span>
                                        @if($absentCount > 0)
                                            /<span class="text-danger">{{ $absentCount }}</span>
                                        @endif
                                    </small>
                                </td>
                            @endforeach
                            <td class="text-center"></td>
                            <td class="text-center"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Résumé --}}
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> {{ __('messages.planning.summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-primary">{{ $planningSummary['total_employees'] }}</div>
                            <small class="text-muted">{{ __('messages.staff.employees') }}</small>
                        </div>
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-success">{{ collect($planning)->sum('total_present') }}</div>
                            <small class="text-muted">{{ __('messages.planning.total_present_days') }}</small>
                        </div>
                        <div class="col-4">
                            <div class="fs-3 fw-bold text-danger">{{ collect($planning)->sum('total_absent') }}</div>
                            <small class="text-muted">{{ __('messages.planning.total_absent_days') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person-x"></i> {{ __('messages.planning.most_absences') }}</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse(collect($planning)->sortByDesc('total_absent')->take(5)->filter(fn($r) => $r['total_absent'] > 0) as $row)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>{{ $row['employee']->name }}</span>
                                <span class="badge bg-danger">{{ $row['total_absent'] }} {{ __('messages.staff.days') }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted text-center">{{ __('messages.planning.no_absences') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
.planning-grid {
    font-size: 0.85rem;
}
.planning-grid th, .planning-grid td {
    vertical-align: middle;
    padding: 0.35rem;
}
.sticky-col {
    position: sticky;
    left: 0;
    z-index: 1;
}
.planning-grid thead {
    z-index: 2;
}
.planning-grid td[title] {
    cursor: help;
}
</style>
