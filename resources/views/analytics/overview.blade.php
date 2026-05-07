@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-speedometer"></i> {{ __('messages.analytics.overview.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.overview.description') }}</p>

    @include('analytics.partials.period-picker')

    {{-- Live + KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-success">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">
                        <span class="me-1" style="color:#28a745">●</span>{{ __('messages.analytics.overview.live_visitors') }}
                    </div>
                    <div class="display-6 fw-bold text-success">{{ number_format($liveVisitors) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.sessions') }}</div>
                    <div class="display-6 fw-bold text-primary">{{ number_format($kpis['sessions']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.unique_visitors') }}</div>
                    <div class="display-6 fw-bold text-info">{{ number_format($kpis['unique_visitors']) }}</div>
                    <div class="small text-muted">{{ number_format($kpis['new_visitors']) }} {{ strtolower(__('messages.analytics.overview.new_visitors')) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.page_views') }}</div>
                    <div class="display-6 fw-bold">{{ number_format($kpis['page_views']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.bounce_rate') }}</div>
                    <div class="display-6 fw-bold">{{ number_format($kpis['bounce_rate'], 1) }}<span class="fs-5">%</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.avg_session_duration') }}</div>
                    <div class="display-6 fw-bold">
                        @php
                            $sec = $kpis['avg_session_duration'];
                            $m = floor($sec / 60); $s = $sec % 60;
                        @endphp
                        {{ $m }}m {{ str_pad($s, 2, '0', STR_PAD_LEFT) }}s
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.conversion_rate') }}</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($kpis['conversion_rate'], 2) }}<span class="fs-5">%</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.revenue') }}</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($kpis['revenue'], 2) }}$</div>
                    <div class="small text-muted">
                        {{ number_format($kpis['orders_paid']) }} {{ __('messages.analytics.overview.orders') }} ·
                        {{ number_format($kpis['aov'], 2) }}$ {{ __('messages.analytics.overview.aov') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GA4 cross-check --}}
    @php
        $ga4Available = !empty($ga4Totals['available']) && !empty($ga4Totals['rows']);
        $ga4Row = $ga4Available ? $ga4Totals['rows'][0] : [];
    @endphp
    <div class="card mb-4 border-info">
        <div class="card-header bg-info bg-opacity-10 d-flex align-items-center">
            <i class="bi bi-google me-2"></i>
            <h6 class="mb-0">{{ __('messages.analytics.overview.ga4_section') }}</h6>
        </div>
        <div class="card-body">
            @if(!$ga4Available)
                <p class="text-muted mb-0">{{ __('messages.analytics.overview.ga4_unavailable') }}
                    @if(!empty($ga4Totals['error']))
                        <small class="d-block mt-1">{{ $ga4Totals['error'] }}</small>
                    @endif
                </p>
            @else
                <div class="row g-3">
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_realtime') }}</div>
                        <div class="display-6 fw-bold text-success">
                            <span class="me-1" style="color:#28a745">●</span>{{ number_format($ga4Realtime) }}
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_sessions') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['sessions'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_users') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['activeUsers'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_new_users') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['newUsers'] ?? 0)) }}</div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_engagement_rate') }}</div>
                        <div class="display-6 fw-bold">{{ number_format(100 * (float)($ga4Row['engagementRate'] ?? 0), 1) }}<span class="fs-5">%</span></div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="text-muted small text-uppercase">{{ __('messages.analytics.overview.ga4_pageviews') }}</div>
                        <div class="display-6 fw-bold">{{ number_format((float)($ga4Row['screenPageViews'] ?? 0)) }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Daily chart --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3">{{ __('messages.analytics.overview.evolution') }}</h5>
            @if($daily->isEmpty())
                <p class="text-muted">{{ __('messages.analytics.overview.no_data') }}</p>
            @else
                <div style="position:relative;height:280px">
                    <canvas id="dailyChart"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- Donuts row --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.sources_chart') }}</h6>
                    @if($sources->isEmpty())
                        <p class="text-muted small mb-0">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <div style="position:relative;height:220px">
                            <canvas id="sourcesChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.devices_chart') }}</h6>
                    @if($devices->isEmpty())
                        <p class="text-muted small mb-0">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <div style="position:relative;height:220px">
                            <canvas id="devicesChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.top_countries') }}</h6>
                    @if($topGeo->isEmpty())
                        <p class="text-muted small mb-0">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <div style="position:relative;height:220px">
                            <canvas id="geoChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top lists --}}
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.top_viewed') }}</h6>
                    @if($topViewed->isEmpty())
                        <p class="text-muted small">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($topViewed as $p)
                                <tr>
                                    <td class="text-truncate" style="max-width:180px" title="{{ $p->product_name }}">{{ $p->product_name }}</td>
                                    <td class="text-end fw-bold">{{ number_format($p->views) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.top_purchased') }}</h6>
                    @if($topPurchased->isEmpty())
                        <p class="text-muted small">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($topPurchased as $p)
                                <tr>
                                    <td class="text-truncate" style="max-width:140px" title="{{ $p->product_name }}">{{ $p->product_name }}</td>
                                    <td class="text-end">{{ number_format($p->units_sold) }}x</td>
                                    <td class="text-end fw-bold">{{ number_format($p->revenue, 2) }}$</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6>{{ __('messages.analytics.overview.top_searches') }}</h6>
                    @if($topSearches->isEmpty())
                        <p class="text-muted small">{{ __('messages.analytics.overview.no_data') }}</p>
                    @else
                        <table class="table table-sm mb-0">
                            <tbody>
                                @foreach($topSearches as $s)
                                <tr>
                                    <td class="text-truncate" style="max-width:180px">{{ $s->term }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold">{{ number_format($s->count) }}</span>
                                        @if($s->zero_results_count)
                                            <small class="text-warning" title="{{ $s->zero_results_count }} zero-results">⚠</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    @if($daily->isNotEmpty())
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: @json($daily->pluck('date')),
            datasets: [
                {
                    label: @json(__('messages.analytics.overview.sessions')),
                    data: @json($daily->pluck('visits')),
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)',
                    yAxisID: 'y', tension: 0.3, fill: true,
                },
                {
                    label: @json(__('messages.analytics.overview.orders')),
                    data: @json($daily->pluck('orders_paid')),
                    borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)',
                    yAxisID: 'y1', tension: 0.3,
                },
                {
                    label: @json(__('messages.analytics.overview.revenue')),
                    data: @json($daily->pluck('revenue')),
                    borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)',
                    yAxisID: 'y1', tension: 0.3, borderDash: [4,4],
                },
                @if(!empty($ga4DailyByDate))
                {
                    label: 'GA4 Sessions',
                    data: @json($daily->map(fn($d) => $ga4DailyByDate[(string)$d->date] ?? null)),
                    borderColor: '#a855f7', backgroundColor: 'rgba(168,85,247,0.1)',
                    yAxisID: 'y', tension: 0.3, borderDash: [2,2],
                },
                @endif
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y:  { type: 'linear', position: 'left',  beginAtZero: true, title: {display:true,text:'Sessions'} },
                y1: { type: 'linear', position: 'right', beginAtZero: true, grid: {drawOnChartArea:false}, title: {display:true,text:'Orders / $'} }
            }
        }
    });
    @endif

    var mkDonut = function (id, labels, data, colors) {
        var el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    };

    @if($sources->isNotEmpty())
    mkDonut('sourcesChart',
        @json($sources->pluck('source_category')),
        @json($sources->pluck('sessions')),
        ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#64748b']
    );
    @endif

    @if($devices->isNotEmpty())
    mkDonut('devicesChart',
        @json($devices->pluck('device_type')),
        @json($devices->pluck('sessions')),
        ['#6366f1','#14b8a6','#f43f5e','#64748b']
    );
    @endif

    @if($topGeo->isNotEmpty())
    mkDonut('geoChart',
        @json($topGeo->pluck('country_code')),
        @json($topGeo->pluck('sessions')),
        ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4']
    );
    @endif
})();
</script>
@endsection
