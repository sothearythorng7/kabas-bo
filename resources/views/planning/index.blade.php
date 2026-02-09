@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title mb-0">
            <i class="bi bi-calendar3"></i> {{ __('messages.planning.title') }}
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('planning.monthly') }}" class="btn btn-outline-success">
                <i class="bi bi-calendar-week"></i> {{ __('messages.planning.monthly_view') }}
            </a>
            <a href="{{ route('staff.index', ['tab' => 'performance']) }}" class="btn btn-outline-primary">
                <i class="bi bi-graph-up"></i> {{ __('messages.staff.performances_btn') }}
            </a>
            <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-warning">
                <i class="bi bi-calendar-check"></i> {{ __('messages.menu.leave_requests') }}
            </a>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
            </a>
        </div>
    </div>

    {{-- Store Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="store_id" class="form-label">{{ __('messages.staff.store') }}</label>
                    <select class="form-select" id="store_id" name="store_id" onchange="this.form.submit()">
                        <option value="">{{ __('messages.staff.all_stores') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $selectedStore == $store->id ? 'selected' : '' }}>
                                {{ $store->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary p-2"><i class="bi bi-circle-fill"></i> {{ __('messages.staff.leave_types.vacation') }}</span>
                        <span class="badge bg-warning p-2"><i class="bi bi-circle-fill"></i> {{ __('messages.staff.leave_types.sick') }}</span>
                        <span class="badge bg-info p-2"><i class="bi bi-circle-fill"></i> {{ __('messages.staff.leave_types.dayoff') }}</span>
                        <span class="badge bg-danger p-2"><i class="bi bi-circle-fill"></i> {{ __('messages.staff.leave_types.unjustified') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Calendar --}}
    <div class="card">
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

{{-- Event Detail Modal --}}
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">{{ __('messages.planning.absence_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-borderless">
                    <tr>
                        <td class="text-muted">{{ __('messages.staff.name') }}:</td>
                        <td><strong id="eventUserName"></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.staff.leave_type') }}:</td>
                        <td><span id="eventType" class="badge"></span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.staff.period') }}:</td>
                        <td id="eventPeriod"></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.staff.days') }}:</td>
                        <td id="eventDays"></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.staff.status') }}:</td>
                        <td><span id="eventStatus" class="badge"></span></td>
                    </tr>
                    <tr id="eventReasonRow">
                        <td class="text-muted">{{ __('messages.staff.reason') }}:</td>
                        <td id="eventReason"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <a href="#" id="viewEmployeeLink" class="btn btn-primary">
                    <i class="bi bi-person"></i> {{ __('messages.planning.view_employee') }}
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
    #calendar {
        max-width: 100%;
        margin: 0 auto;
    }
    .fc-event {
        cursor: pointer;
    }
    .fc-event.opacity-50 {
        opacity: 0.5;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.8/locales/fr.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const storeId = '{{ $selectedStore }}';

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: '{{ app()->getLocale() }}',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(info, successCallback, failureCallback) {
            let url = '{{ route("planning.events") }}?start=' + info.startStr + '&end=' + info.endStr;
            if (storeId) {
                url += '&store_id=' + storeId;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps;

            document.getElementById('eventUserName').textContent = props.user_name;
            document.getElementById('eventType').textContent = props.type_label;
            document.getElementById('eventType').className = 'badge bg-' + getTypeBadge(props.type);
            document.getElementById('eventPeriod').textContent = formatDate(event.start) + (event.end ? ' - ' + formatDate(new Date(event.end.getTime() - 86400000)) : '');
            document.getElementById('eventDays').textContent = props.days + ' {{ __("messages.staff.days") }}';
            document.getElementById('eventStatus').textContent = getStatusLabel(props.status);
            document.getElementById('eventStatus').className = 'badge bg-' + getStatusBadge(props.status);

            if (props.reason) {
                document.getElementById('eventReasonRow').style.display = '';
                document.getElementById('eventReason').textContent = props.reason;
            } else {
                document.getElementById('eventReasonRow').style.display = 'none';
            }

            document.getElementById('viewEmployeeLink').href = '{{ url("staff") }}/' + props.staff_member_id + '?tab=leaves';

            new bootstrap.Modal(document.getElementById('eventModal')).show();
        },
        height: 'auto',
        eventDisplay: 'block'
    });

    calendar.render();

    function getTypeBadge(type) {
        const badges = {
            'vacation': 'primary',
            'sick': 'warning',
            'dayoff': 'info',
            'unjustified': 'danger'
        };
        return badges[type] || 'secondary';
    }

    function getStatusBadge(status) {
        const badges = {
            'pending': 'warning',
            'approved': 'success',
            'rejected': 'danger'
        };
        return badges[status] || 'secondary';
    }

    function getStatusLabel(status) {
        const labels = {
            'pending': '{{ __("messages.staff.leave_status.pending") }}',
            'approved': '{{ __("messages.staff.leave_status.approved") }}',
            'rejected': '{{ __("messages.staff.leave_status.rejected") }}'
        };
        return labels[status] || status;
    }

    function formatDate(date) {
        return date.toLocaleDateString('{{ app()->getLocale() }}', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
});
</script>
@endpush
@endsection
