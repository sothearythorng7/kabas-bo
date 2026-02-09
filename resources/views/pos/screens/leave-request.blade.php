<div id="screen-leaverequest" class="pos-screen d-none" style="height: 100vh; overflow-y: auto; padding-bottom: 20px;">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <button id="btn-leave-back" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h5 class="mb-0">
                <i class="bi bi-calendar-plus"></i> {{ __('messages.staff.request_leave') }}
            </h5>
        </div>
    </div>

    <!-- Leave Balances (compact) -->
    <div class="card mb-3" id="leave-balances-card">
        <div class="card-body py-2">
            <div class="row text-center" id="leave-balances">
                <!-- Dynamic content -->
            </div>
        </div>
    </div>

    <!-- Request Form -->
    <div class="card mb-3">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="bi bi-pencil"></i> {{ __('messages.my_leaves.new_request') }}</h6>
        </div>
        <div class="card-body py-2">
            <!-- Leave Type (dropdown) -->
            <div class="mb-3">
                <label class="form-label fw-bold mb-1">{{ __('messages.staff.leave_type') }}</label>
                <select class="form-select" id="leave-type-select">
                    <option value="vacation">{{ __('messages.staff.leave_types.vacation') }}</option>
                    <option value="sick">{{ __('messages.staff.leave_types.sick') }}</option>
                    <option value="dayoff">{{ __('messages.staff.leave_types.dayoff') }}</option>
                </select>
            </div>

            <!-- Dates -->
            <div class="row mb-2">
                <div class="col-6">
                    <label class="form-label fw-bold mb-1">{{ __('messages.staff.start_date') }}</label>
                    <input type="date" class="form-control" id="leave-start-date">
                </div>
                <div class="col-6">
                    <label class="form-label fw-bold mb-1">{{ __('messages.staff.end_date') }}</label>
                    <input type="date" class="form-control" id="leave-end-date">
                </div>
            </div>

            <!-- Days count info -->
            <div class="alert alert-info py-2 mb-2" id="leave-days-info" style="display: none;">
                <small><i class="bi bi-info-circle"></i> <span id="leave-days-text"></span></small>
            </div>

            <!-- Reason -->
            <div class="mb-2">
                <label class="form-label fw-bold mb-1">{{ __('messages.staff.reason') }} <small class="text-muted">({{ __('messages.optional') }})</small></label>
                <textarea class="form-control" id="leave-reason" rows="2" placeholder="{{ __('messages.staff.reason_placeholder') }}"></textarea>
            </div>

            <!-- Error -->
            <div class="alert alert-danger py-2 mb-2" id="leave-error" style="display: none;"></div>

            <!-- Success -->
            <div class="alert alert-success py-2 mb-2" id="leave-success" style="display: none;"></div>

            <!-- Submit -->
            <div class="d-grid">
                <button class="btn btn-primary" id="btn-submit-leave" disabled>
                    <i class="bi bi-send"></i> {{ __('messages.my_leaves.submit_request') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="card" id="leave-recent-card" style="display: none;">
        <div class="card-header py-2">
            <h6 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('messages.staff.recent_requests') }}</h6>
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
                <tbody id="leave-recent-body">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function() {
    let selectedLeaveType = 'vacation';
    let leaveBalances = [];

    window.initLeaverequest = function() {
        selectedLeaveType = 'vacation';
        $("#leave-type-select").val('vacation');
        $("#leave-start-date, #leave-end-date, #leave-reason").val('');
        $("#leave-error, #leave-success, #leave-days-info").hide();
        $("#btn-submit-leave").prop('disabled', true);

        // Set min date to today
        const today = new Date().toISOString().slice(0, 10);
        $("#leave-start-date").attr('min', today).val(today);
        $("#leave-end-date").attr('min', today).val(today);

        loadLeaveData();
    };

    function loadLeaveData() {
        if (!window.currentUser || !window.currentUser.id) return;

        // Load balances
        fetch(`{{ config('app.url') }}/api/pos/planning/user-balance/${window.currentUser.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    leaveBalances = data.data;
                    renderLeaveBalances(data.data);
                }
            });

        // Load recent leaves
        fetch(`{{ config('app.url') }}/api/pos/planning/user-leaves/${window.currentUser.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length) {
                    renderRecentLeaves(data.data);
                    $("#leave-recent-card").show();
                } else {
                    $("#leave-recent-card").hide();
                }
            });
    }

    function renderLeaveBalances(balances) {
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

        let html = '';
        balances.forEach(balance => {
            const label = labels[balance.type] || balance.type;
            const color = colors[balance.type] || 'secondary';

            html += `
                <div class="col-4">
                    <div class="bg-${color} bg-opacity-10 rounded p-2">
                        <div class="fs-4 fw-bold">${balance.remaining}<small class="text-muted">/${balance.annual_quota}</small></div>
                        <div class="small">${label}</div>
                    </div>
                </div>
            `;
        });

        $("#leave-balances").html(html);
    }

    function renderRecentLeaves(leaves) {
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
        leaves.slice(0, 5).forEach(leave => {
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

        $("#leave-recent-body").html(html);
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function calculateDays() {
        const start = $("#leave-start-date").val();
        const end = $("#leave-end-date").val();

        if (!start || !end) {
            $("#leave-days-info").hide();
            $("#btn-submit-leave").prop('disabled', true);
            return 0;
        }

        const startDate = new Date(start);
        const endDate = new Date(end);
        const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;

        if (days <= 0) {
            $("#leave-days-info").hide();
            $("#btn-submit-leave").prop('disabled', true);
            return 0;
        }

        // Find remaining balance for selected type
        const balance = leaveBalances.find(b => b.type === selectedLeaveType);
        const remaining = balance ? balance.remaining : 0;
        const remainingAfter = remaining - days;

        let text = `{{ __('messages.staff.duration') }}: <strong>${days}j</strong>`;
        if (balance) {
            text += ` | {{ __('messages.staff.remaining_after') }}: <strong class="${remainingAfter < 0 ? 'text-danger' : ''}">${remainingAfter}j</strong>`;
        }

        $("#leave-days-text").html(text);
        $("#leave-days-info").show();
        $("#btn-submit-leave").prop('disabled', remainingAfter < 0);

        return days;
    }

    // Event handlers
    $(document).on("change", "#leave-type-select", function() {
        selectedLeaveType = $(this).val();
        calculateDays();
    });

    $(document).on("change", "#leave-start-date", function() {
        const start = $(this).val();
        $("#leave-end-date").attr('min', start);
        if ($("#leave-end-date").val() < start) {
            $("#leave-end-date").val(start);
        }
        calculateDays();
    });

    $(document).on("change", "#leave-end-date", function() {
        calculateDays();
    });

    $(document).on("click", "#btn-leave-back", function() {
        showScreen("myplanning");
        initMyplanning();
    });

    $(document).on("click", "#btn-submit-leave", async function() {
        const startDate = $("#leave-start-date").val();
        const endDate = $("#leave-end-date").val();
        const reason = $("#leave-reason").val();

        if (!startDate || !endDate) {
            $("#leave-error").text('{{ __("messages.staff.please_select_dates") }}').show();
            return;
        }

        $("#leave-error, #leave-success").hide();
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>{{ __("messages.sending") }}');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(`{{ config('app.url') }}/api/pos/planning/request-leave`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    user_id: window.currentUser.id,
                    type: selectedLeaveType,
                    start_date: startDate,
                    end_date: endDate,
                    reason: reason
                })
            });

            const data = await res.json();

            if (data.success) {
                $("#leave-success").text('{{ __("messages.staff.leave_request_sent") }}').show();
                $("#leave-start-date, #leave-end-date, #leave-reason").val('');
                $("#leave-days-info").hide();
                loadLeaveData();
            } else {
                $("#leave-error").text(data.message || '{{ __("messages.error_occurred") }}').show();
            }
        } catch (err) {
            console.error("Error submitting leave request:", err);
            $("#leave-error").text('{{ __("messages.connection_error") }}').show();
        } finally {
            $(this).prop('disabled', false).html('<i class="bi bi-send"></i> {{ __("messages.my_leaves.submit_request") }}');
        }
    });
})();
</script>
@endpush
