<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('messages.staff.weekly_schedule') }}</h5>
        <span class="badge bg-primary fs-6">
            {{ __('messages.staff.total_hours') }}: {{ number_format($totalHours, 1) }}h
        </span>
    </div>
    <div class="card-body">
        <form action="{{ route('staff.schedule.update', $staffMember) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 150px;">{{ __('messages.staff.day') }}</th>
                            <th class="text-center" style="width: 100px;">{{ __('messages.staff.working_day') }}</th>
                            <th style="width: 150px;">{{ __('messages.staff.start_time') }}</th>
                            <th style="width: 150px;">{{ __('messages.staff.end_time') }}</th>
                            <th class="text-center">{{ __('messages.staff.hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $dayNames = [
                                1 => __('messages.staff.weekdays.monday'),
                                2 => __('messages.staff.weekdays.tuesday'),
                                3 => __('messages.staff.weekdays.wednesday'),
                                4 => __('messages.staff.weekdays.thursday'),
                                5 => __('messages.staff.weekdays.friday'),
                                6 => __('messages.staff.weekdays.saturday'),
                                0 => __('messages.staff.weekdays.sunday'),
                            ];
                        @endphp
                        @foreach([1, 2, 3, 4, 5, 6, 0] as $day)
                            @php $schedule = $schedules[$day]; @endphp
                            <tr>
                                <td>
                                    <strong>{{ $dayNames[$day] }}</strong>
                                    <input type="hidden" name="schedules[{{ $day }}][day_of_week]" value="{{ $day }}">
                                </td>
                                <td class="text-center">
                                    <div class="form-check d-flex justify-content-center">
                                        <input type="checkbox" class="form-check-input working-day-checkbox"
                                               name="schedules[{{ $day }}][is_working_day]"
                                               value="1"
                                               data-day="{{ $day }}"
                                               {{ $schedule->is_working_day ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm time-input"
                                           name="schedules[{{ $day }}][start_time]"
                                           data-day="{{ $day }}"
                                           value="{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '' }}"
                                           {{ $schedule->is_working_day ? '' : 'disabled' }}>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm time-input"
                                           name="schedules[{{ $day }}][end_time]"
                                           data-day="{{ $day }}"
                                           value="{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '' }}"
                                           {{ $schedule->is_working_day ? '' : 'disabled' }}>
                                </td>
                                <td class="text-center">
                                    <span class="hours-display" data-day="{{ $day }}">
                                        {{ $schedule->is_working_day ? number_format($schedule->getHoursWorked(), 1) . 'h' : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ __('messages.btn.save') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle time inputs based on working day checkbox
    document.querySelectorAll('.working-day-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const day = this.dataset.day;
            const timeInputs = document.querySelectorAll('.time-input[data-day="' + day + '"]');
            const hoursDisplay = document.querySelector('.hours-display[data-day="' + day + '"]');

            timeInputs.forEach(function(input) {
                input.disabled = !checkbox.checked;
                if (!checkbox.checked) {
                    input.value = '';
                }
            });

            if (!checkbox.checked && hoursDisplay) {
                hoursDisplay.textContent = '-';
            }
        });
    });

    // Calculate hours when time changes
    document.querySelectorAll('.time-input').forEach(function(input) {
        input.addEventListener('change', function() {
            const day = this.dataset.day;
            const startInput = document.querySelector('input[name="schedules[' + day + '][start_time]"]');
            const endInput = document.querySelector('input[name="schedules[' + day + '][end_time]"]');
            const hoursDisplay = document.querySelector('.hours-display[data-day="' + day + '"]');

            if (startInput.value && endInput.value && hoursDisplay) {
                const start = new Date('2000-01-01 ' + startInput.value);
                const end = new Date('2000-01-01 ' + endInput.value);
                const hours = (end - start) / 1000 / 60 / 60;
                hoursDisplay.textContent = hours > 0 ? hours.toFixed(1) + 'h' : '-';
            }
        });
    });
});
</script>
