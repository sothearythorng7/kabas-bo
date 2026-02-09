@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">
            <i class="bi bi-calendar-week"></i> {{ __('messages.my_planning.title') }}
            <small class="text-muted fs-5">- {{ $monthStart->translatedFormat('F Y') }}</small>
        </h1>
        <a href="{{ route('my-leaves.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-calendar-check"></i> {{ __('messages.my_leaves.menu_title') }}
        </a>
    </div>

    {{-- Sélecteur de mois --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="month" class="form-label">{{ __('messages.planning.month') }}</label>
                    <input type="month" class="form-control" id="month" name="month"
                           value="{{ $month }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-9">
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

    {{-- Calendrier --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $dayName)
                                <th class="text-center" style="width: 14.28%;">
                                    {{ __('messages.days_short.' . $dayName) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $firstDay = \Carbon\Carbon::parse($month . '-01');
                            $startOfWeek = $firstDay->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                            $endOfMonth = $firstDay->copy()->endOfMonth();
                            $endOfWeek = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                            $currentDate = $startOfWeek->copy();
                        @endphp

                        @while($currentDate <= $endOfWeek)
                            <tr>
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $dayNum = $currentDate->day;
                                        $isCurrentMonth = $currentDate->month === $firstDay->month;
                                        $isToday = $currentDate->isToday();
                                        $dayData = $isCurrentMonth && isset($days[$dayNum]) ? $days[$dayNum] : null;

                                        $cellClass = '';
                                        $icon = '';
                                        $title = '';

                                        if (!$isCurrentMonth) {
                                            $cellClass = 'bg-light text-muted';
                                        } elseif ($dayData) {
                                            if ($dayData['status'] === 'present') {
                                                $cellClass = 'bg-success-subtle text-success';
                                                $icon = '<i class="bi bi-check-lg"></i>';
                                                $title = __('messages.planning.present');
                                            } elseif ($dayData['status'] === 'absent') {
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
                                                    'unjustified' => '<i class="bi bi-x-lg"></i>',
                                                ];
                                                $cellClass = $typeColors[$dayData['type']] ?? 'bg-secondary text-white';
                                                if ($dayData['leave_status'] === 'pending') {
                                                    $cellClass .= ' opacity-50';
                                                }
                                                $icon = $typeIcons[$dayData['type']] ?? '';
                                                $title = __('messages.staff.leave_types.' . $dayData['type']);
                                                if ($dayData['leave_status'] === 'pending') {
                                                    $title .= ' (' . __('messages.staff.leave_status.pending') . ')';
                                                }
                                            } else {
                                                $cellClass = $currentDate->isWeekend() ? 'bg-light' : 'bg-secondary-subtle';
                                                $title = __('messages.planning.day_off');
                                            }
                                        }
                                    @endphp
                                    <td class="text-center {{ $cellClass }} p-2 {{ $isToday ? 'border-primary border-3' : '' }}"
                                        style="height: 70px; vertical-align: middle;"
                                        title="{{ $title }}">
                                        @if($isCurrentMonth)
                                            <div class="fw-bold {{ $isToday ? 'text-primary' : '' }}">{{ $dayNum }}</div>
                                            <div class="fs-5">{!! $icon !!}</div>
                                        @else
                                            <span class="text-muted">{{ $dayNum }}</span>
                                        @endif
                                    </td>
                                    @php $currentDate->addDay(); @endphp
                                @endfor
                            </tr>
                        @endwhile
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Résumé --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success-subtle h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-success">{{ $totalPresent }}</div>
                    <div>{{ __('messages.my_planning.working_days') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger-subtle h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-danger">{{ $totalAbsent }}</div>
                    <div>{{ __('messages.my_planning.absence_days') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary-subtle h-100">
                <div class="card-body text-center">
                    <div class="fs-1 fw-bold text-secondary">{{ $totalOff }}</div>
                    <div>{{ __('messages.my_planning.off_days') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Détail des congés du mois --}}
    @if($leaves->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-x"></i> {{ __('messages.my_planning.leaves_this_month') }}</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.staff.leave_type') }}</th>
                            <th>{{ __('messages.staff.period') }}</th>
                            <th class="text-center">{{ __('messages.staff.days') }}</th>
                            <th>{{ __('messages.staff.status') }}</th>
                            <th>{{ __('messages.staff.reason') }}</th>
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
                                    @if(!$leave->start_date->eq($leave->end_date))
                                        - {{ $leave->end_date->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $leave->getDaysCount() }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $leave->getStatusBadgeClass() }}">
                                        {{ $leave->getStatusLabel() }}
                                    </span>
                                </td>
                                <td>{{ $leave->reason ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
