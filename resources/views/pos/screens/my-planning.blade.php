<div id="screen-myplanning" class="pos-screen d-none" style="height: 100vh; overflow-y: auto; padding-bottom: 20px;">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <button id="btn-planning-back" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h5 class="mb-0">
                <i class="bi bi-calendar-week"></i> {{ __('messages.my_planning.title') }}
                <small class="text-muted" id="planning-month-label"></small>
            </h5>
        </div>
        <button class="btn btn-success btn-sm" id="btn-planning-to-leave">
            <i class="bi bi-calendar-plus"></i> {{ __('messages.staff.request_leave') }}
        </button>
    </div>

    <!-- Month navigation -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center justify-content-between">
                <button class="btn btn-sm btn-outline-secondary" id="btn-planning-prev-month">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <input type="month" class="form-control form-control-sm mx-2" id="planning-month-input" style="max-width: 200px;">
                <button class="btn btn-sm btn-outline-secondary" id="btn-planning-next-month">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex gap-2 flex-wrap mb-3 small">
        <span class="badge bg-success"><i class="bi bi-check"></i> {{ __('messages.planning.present') }}</span>
        <span class="badge bg-primary"><i class="bi bi-airplane"></i> {{ __('messages.staff.leave_types.vacation') }}</span>
        <span class="badge bg-warning text-dark"><i class="bi bi-bandaid"></i> {{ __('messages.staff.leave_types.sick') }}</span>
        <span class="badge bg-info"><i class="bi bi-cup-hot"></i> {{ __('messages.staff.leave_types.dayoff') }}</span>
        <span class="badge bg-secondary">{{ __('messages.planning.day_off') }}</span>
    </div>

    <!-- Loading -->
    <div id="planning-loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('messages.loading') }}...</span>
        </div>
    </div>

    <!-- Calendar -->
    <div class="card mb-3" id="planning-calendar-card" style="display: none;">
        <div class="card-body p-2">
            <table class="table table-bordered mb-0" id="planning-calendar-table">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">{{ __('messages.days_short.mon') }}</th>
                        <th class="text-center">{{ __('messages.days_short.tue') }}</th>
                        <th class="text-center">{{ __('messages.days_short.wed') }}</th>
                        <th class="text-center">{{ __('messages.days_short.thu') }}</th>
                        <th class="text-center">{{ __('messages.days_short.fri') }}</th>
                        <th class="text-center">{{ __('messages.days_short.sat') }}</th>
                        <th class="text-center">{{ __('messages.days_short.sun') }}</th>
                    </tr>
                </thead>
                <tbody id="planning-calendar-body">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary -->
    <div class="row mb-3" id="planning-summary" style="display: none;">
        <div class="col-4">
            <div class="card bg-success bg-opacity-25 h-100">
                <div class="card-body text-center py-2">
                    <div class="fs-3 fw-bold text-success" id="planning-total-present">0</div>
                    <div class="small">{{ __('messages.my_planning.working_days') }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card bg-danger bg-opacity-25 h-100">
                <div class="card-body text-center py-2">
                    <div class="fs-3 fw-bold text-danger" id="planning-total-absent">0</div>
                    <div class="small">{{ __('messages.my_planning.absence_days') }}</div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card bg-secondary bg-opacity-25 h-100">
                <div class="card-body text-center py-2">
                    <div class="fs-3 fw-bold text-secondary" id="planning-total-off">0</div>
                    <div class="small">{{ __('messages.my_planning.off_days') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balances -->
    <div class="card mb-3" id="planning-balances-card" style="display: none;">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-pie-chart"></i> {{ __('messages.staff.leave_balance') }}</h6>
        </div>
        <div class="card-body">
            <div class="row" id="planning-balances">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <!-- Leaves list -->
    <div class="card" id="planning-leaves-card" style="display: none;">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-calendar-x"></i> {{ __('messages.my_planning.leaves_this_month') }}</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.staff.leave_type') }}</th>
                        <th>{{ __('messages.staff.period') }}</th>
                        <th class="text-center">{{ __('messages.staff.days') }}</th>
                        <th>{{ __('messages.staff.status') }}</th>
                    </tr>
                </thead>
                <tbody id="planning-leaves-body">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function() {
    let planningMonth = new Date().toISOString().slice(0, 7);

    window.initMyplanning = function() {
        planningMonth = new Date().toISOString().slice(0, 7);
        $("#planning-month-input").val(planningMonth);
        loadPlanningData();
    };

    function loadPlanningData() {
        if (!window.currentUser || !window.currentUser.id) {
            console.error("No current user");
            return;
        }

        $("#planning-loading").show();
        $("#planning-calendar-card, #planning-summary, #planning-balances-card, #planning-leaves-card").hide();

        fetch(`{{ config('app.url') }}/api/pos/planning/user-planning/${window.currentUser.id}?month=${planningMonth}`)
            .then(res => res.json())
            .then(data => {
                $("#planning-loading").hide();
                if (data.success) {
                    renderPlanning(data.data);
                } else {
                    console.error("Error loading planning:", data.message);
                }
            })
            .catch(err => {
                $("#planning-loading").hide();
                console.error("Error loading planning:", err);
            });
    }

    function renderPlanning(data) {
        // Month label
        const monthDate = new Date(planningMonth + '-01');
        const monthLabel = monthDate.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
        $("#planning-month-label").text(' - ' + monthLabel);

        // Calendar
        renderCalendar(data.days, planningMonth);
        $("#planning-calendar-card").show();

        // Summary
        $("#planning-total-present").text(data.totals.present);
        $("#planning-total-absent").text(data.totals.absent);
        $("#planning-total-off").text(data.totals.off);
        $("#planning-summary").show();

        // Balances
        if (data.balances && data.balances.length) {
            renderBalances(data.balances);
            $("#planning-balances-card").show();
        }

        // Leaves
        if (data.leaves && data.leaves.length) {
            renderLeaves(data.leaves);
            $("#planning-leaves-card").show();
        }
    }

    function renderCalendar(days, month) {
        const [year, monthNum] = month.split('-').map(Number);
        const firstDay = new Date(year, monthNum - 1, 1);
        const lastDay = new Date(year, monthNum, 0);

        // Start from Monday
        const startDayOfWeek = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - startDayOfWeek);

        const today = new Date();
        let html = '';
        let currentDate = new Date(startDate);

        while (currentDate <= lastDay || currentDate.getDay() !== 1) {
            html += '<tr>';
            for (let i = 0; i < 7; i++) {
                const isCurrentMonth = currentDate.getMonth() === monthNum - 1;
                const dayNum = currentDate.getDate();
                const dayData = isCurrentMonth && days[dayNum] ? days[dayNum] : null;
                const isToday = currentDate.toDateString() === today.toDateString();

                let cellClass = '';
                let icon = '';

                if (!isCurrentMonth) {
                    cellClass = 'bg-light text-muted';
                } else if (dayData) {
                    if (dayData.status === 'present') {
                        cellClass = 'bg-success bg-opacity-25 text-success';
                        icon = '<i class="bi bi-check-lg"></i>';
                    } else if (dayData.status === 'absent') {
                        const opacity = dayData.leave_status === 'pending' ? 'opacity-50' : '';
                        switch (dayData.type) {
                            case 'vacation':
                                cellClass = `bg-primary text-white ${opacity}`;
                                icon = '<i class="bi bi-airplane"></i>';
                                break;
                            case 'sick':
                                cellClass = `bg-warning ${opacity}`;
                                icon = '<i class="bi bi-bandaid"></i>';
                                break;
                            case 'dayoff':
                                cellClass = `bg-info text-white ${opacity}`;
                                icon = '<i class="bi bi-cup-hot"></i>';
                                break;
                            default:
                                cellClass = `bg-danger text-white ${opacity}`;
                                icon = '<i class="bi bi-x-lg"></i>';
                        }
                    } else {
                        cellClass = 'bg-secondary bg-opacity-10';
                    }
                }

                const borderClass = isToday ? 'border-primary border-3' : '';

                html += `<td class="text-center ${cellClass} ${borderClass} p-1" style="height: 50px; vertical-align: middle;">`;
                if (isCurrentMonth) {
                    html += `<div class="fw-bold ${isToday ? 'text-primary' : ''}">${dayNum}</div>`;
                    html += `<div class="small">${icon}</div>`;
                } else {
                    html += `<span class="text-muted">${dayNum}</span>`;
                }
                html += '</td>';

                currentDate.setDate(currentDate.getDate() + 1);
            }
            html += '</tr>';
            if (currentDate.getMonth() !== monthNum - 1 && currentDate.getDay() === 1) break;
        }

        $("#planning-calendar-body").html(html);
    }

    function renderBalances(balances) {
        let html = '';
        const labels = {
            vacation: '{{ __("messages.staff.leave_types.vacation") }}',
            sick: '{{ __("messages.staff.leave_types.sick") }}',
            dayoff: '{{ __("messages.staff.leave_types.dayoff") }}'
        };
        const colors = {
            vacation: 'primary',
            sick: 'warning',
            dayoff: 'info'
        };

        balances.forEach(balance => {
            const label = labels[balance.type] || balance.type;
            const color = colors[balance.type] || 'secondary';
            const pct = balance.annual_quota > 0 ? Math.round((balance.remaining / balance.annual_quota) * 100) : 0;

            html += `
                <div class="col-md-4 mb-2">
                    <div class="small mb-1">
                        <span>${label}</span>
                        <span class="float-end">${balance.remaining} / ${balance.annual_quota} j</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-${color}" style="width: ${pct}%"></div>
                    </div>
                </div>
            `;
        });

        $("#planning-balances").html(html);
    }

    function renderLeaves(leaves) {
        const statusLabels = {
            pending: '{{ __("messages.staff.leave_status.pending") }}',
            approved: '{{ __("messages.staff.leave_status.approved") }}',
            rejected: '{{ __("messages.staff.leave_status.rejected") }}'
        };
        const statusColors = {
            pending: 'warning',
            approved: 'success',
            rejected: 'danger'
        };
        const typeLabels = {
            vacation: '{{ __("messages.staff.leave_types.vacation") }}',
            sick: '{{ __("messages.staff.leave_types.sick") }}',
            dayoff: '{{ __("messages.staff.leave_types.dayoff") }}'
        };
        const typeColors = {
            vacation: 'primary',
            sick: 'warning',
            dayoff: 'info'
        };

        let html = '';
        leaves.forEach(leave => {
            const typeLabel = typeLabels[leave.type] || leave.type;
            const typeColor = typeColors[leave.type] || 'secondary';
            const statusLabel = statusLabels[leave.status] || leave.status;
            const statusColor = statusColors[leave.status] || 'secondary';

            html += `
                <tr>
                    <td><span class="badge bg-${typeColor}">${typeLabel}</span></td>
                    <td>${formatDate(leave.start_date)} - ${formatDate(leave.end_date)}</td>
                    <td class="text-center">${leave.days}</td>
                    <td><span class="badge bg-${statusColor}">${statusLabel}</span></td>
                </tr>
            `;
        });

        $("#planning-leaves-body").html(html);
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
    }

    // Event handlers
    $(document).on("click", "#btn-planning-back", function() {
        if (window.currentShift && window.currentShift.id) {
            showScreen("dashboard");
        } else {
            showScreen("shiftstart");
        }
    });

    $(document).on("click", "#btn-planning-to-leave", function() {
        showScreen("leaverequest");
        initLeaverequest();
    });

    $(document).on("change", "#planning-month-input", function() {
        planningMonth = $(this).val();
        loadPlanningData();
    });

    $(document).on("click", "#btn-planning-prev-month", function() {
        const [year, month] = planningMonth.split('-').map(Number);
        const d = new Date(year, month - 2, 1);
        planningMonth = d.toISOString().slice(0, 7);
        $("#planning-month-input").val(planningMonth);
        loadPlanningData();
    });

    $(document).on("click", "#btn-planning-next-month", function() {
        const [year, month] = planningMonth.split('-').map(Number);
        const d = new Date(year, month, 1);
        planningMonth = d.toISOString().slice(0, 7);
        $("#planning-month-input").val(planningMonth);
        loadPlanningData();
    });
})();
</script>
@endpush
