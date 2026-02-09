{{-- Planning individuel de l'employé --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-calendar-week"></i> {{ __('messages.staff.user_planning_title') }}
        </h5>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="tab" value="planning">
            <input type="month" name="planning_month" class="form-control form-control-sm"
                   value="{{ $userPlanningMonth ?? now()->format('Y-m') }}"
                   onchange="this.form.submit()">
        </form>
    </div>
    <div class="card-body">
        @if(isset($userPlanningData) && !empty($userPlanningData['days']))
            {{-- Légende --}}
            <div class="d-flex gap-3 align-items-center flex-wrap mb-4">
                <span class="badge bg-success"><i class="bi bi-check"></i> {{ __('messages.planning.present') }}</span>
                <span class="badge bg-primary"><i class="bi bi-airplane"></i> {{ __('messages.staff.leave_types.vacation') }}</span>
                <span class="badge bg-warning text-dark"><i class="bi bi-bandaid"></i> {{ __('messages.staff.leave_types.sick') }}</span>
                <span class="badge bg-info"><i class="bi bi-cup-hot"></i> {{ __('messages.staff.leave_types.dayoff') }}</span>
                <span class="badge bg-danger"><i class="bi bi-x"></i> {{ __('messages.staff.leave_types.unjustified') }}</span>
                <span class="badge bg-light text-muted border">{{ __('messages.planning.day_off') }}</span>
            </div>

            {{-- Calendrier du mois --}}
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                                <th class="text-center" style="width: 14.28%;">
                                    {{ __('messages.days_short.' . strtolower($dayName)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $firstDay = \Carbon\Carbon::parse($userPlanningMonth . '-01');
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
                                        $dayData = $isCurrentMonth && isset($userPlanningData['days'][$dayNum])
                                            ? $userPlanningData['days'][$dayNum]
                                            : null;

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
                                                if (isset($dayData['leave_status']) && $dayData['leave_status'] === 'pending') {
                                                    $cellClass .= ' opacity-50';
                                                }
                                                $icon = $typeIcons[$dayData['type']] ?? '';
                                                $title = __('messages.staff.leave_types.' . $dayData['type']);
                                                if (isset($dayData['leave_status']) && $dayData['leave_status'] === 'pending') {
                                                    $title .= ' (' . __('messages.staff.leave_status.pending') . ')';
                                                }
                                            } else {
                                                $cellClass = $currentDate->isWeekend() ? 'bg-light' : 'bg-secondary-subtle';
                                                $title = __('messages.planning.day_off');
                                            }
                                        }
                                    @endphp
                                    <td class="text-center {{ $cellClass }} p-2" style="height: 60px; vertical-align: middle;" title="{{ $title }}">
                                        @if($isCurrentMonth)
                                            <div class="fw-bold">{{ $dayNum }}</div>
                                            <div>{!! $icon !!}</div>
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

            {{-- Résumé du mois --}}
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card bg-success-subtle">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold text-success">{{ $userPlanningData['total_present'] }}</div>
                            <small>{{ __('messages.planning.present') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger-subtle">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold text-danger">{{ $userPlanningData['total_absent'] }}</div>
                            <small>{{ __('messages.staff.absences') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-secondary-subtle">
                        <div class="card-body text-center">
                            <div class="fs-2 fw-bold text-secondary">{{ $userPlanningData['total_off'] }}</div>
                            <small>{{ __('messages.planning.day_off') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Détail des absences du mois --}}
            @if($userPlanningData['leaves']->isNotEmpty())
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-x"></i> {{ __('messages.staff.absences_detail') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.staff.leave_type') }}</th>
                                    <th>{{ __('messages.staff.period') }}</th>
                                    <th class="text-center">{{ __('messages.staff.days') }}</th>
                                    <th>{{ __('messages.staff.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userPlanningData['leaves'] as $leave)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $leave->getTypeBadgeClass() }}">
                                                {{ $leave->getTypeLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $leave->start_date->format('d/m') }}
                                            @if(!$leave->start_date->eq($leave->end_date))
                                                - {{ $leave->end_date->format('d/m') }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $leave->getDaysCount() }}</td>
                                        <td>
                                            <span class="badge bg-{{ $leave->getStatusBadgeClass() }}">
                                                {{ $leave->getStatusLabel() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> {{ __('messages.staff.no_planning_data') }}
            </div>
        @endif
    </div>
</div>
