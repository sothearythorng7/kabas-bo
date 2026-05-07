{{--
    Email statistics tab — reusable partial.

    Required variables:
    - $stats: array with keys [total, today, week, month, daily]
    - $chartId: unique canvas id (string)
--}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="text-muted small text-uppercase">{{ __('messages.email_stats.total') }}</div>
                <div class="display-6 fw-bold text-primary">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="text-muted small text-uppercase">{{ __('messages.email_stats.today') }}</div>
                <div class="display-6 fw-bold text-success">{{ number_format($stats['today']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="text-muted small text-uppercase">{{ __('messages.email_stats.week') }}</div>
                <div class="display-6 fw-bold text-info">{{ number_format($stats['week']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 text-center">
            <div class="card-body">
                <div class="text-muted small text-uppercase">{{ __('messages.email_stats.month') }}</div>
                <div class="display-6 fw-bold text-warning">{{ number_format($stats['month']) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-bar-chart"></i> {{ __('messages.email_stats.chart_title') }}
    </div>
    <div class="card-body">
        <canvas id="{{ $chartId }}" height="90"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function() {
    const el = document.getElementById(@json($chartId));
    if (!el) return;
    const daily = @json($stats['daily']);
    const labels = daily.map(d => {
        const dt = new Date(d.date);
        return dt.toLocaleDateString(undefined, { day: '2-digit', month: '2-digit' });
    });
    const counts = daily.map(d => d.count);
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: @json(__('messages.email_stats.emails_sent')),
                data: counts,
                backgroundColor: 'rgba(37, 129, 50, 0.6)',
                borderColor: 'rgba(37, 129, 50, 1)',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
})();
</script>
