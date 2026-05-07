@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="container-fluid mt-4">
    <h1 class="crud_title">
        <i class="bi bi-globe"></i> {{ __('messages.analytics.geo.title') }}
    </h1>
    <p class="text-muted">{{ __('messages.analytics.geo.description') }}</p>

    @include('analytics.partials.period-picker')

    <div class="card mb-4">
        <div class="card-body p-0">
            <div id="geoMap" style="height: 420px; border-radius: 0.5rem;"></div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.analytics.geo.country') }}</th>
                        <th class="text-end">{{ __('messages.analytics.geo.sessions') }}</th>
                        <th class="text-end">{{ __('messages.analytics.geo.orders') }}</th>
                        <th class="text-end">{{ __('messages.analytics.geo.revenue') }}</th>
                        <th class="text-end">{{ __('messages.analytics.geo.conversion') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td>
                                <span class="me-1" style="font-family:monospace;background:#f3f4f6;padding:2px 6px;border-radius:3px">{{ $r->country_code }}</span>
                            </td>
                            <td class="text-end">{{ number_format($r->sessions) }}</td>
                            <td class="text-end">{{ number_format($r->orders) }}</td>
                            <td class="text-end">{{ number_format($r->revenue, 2) }}$</td>
                            <td class="text-end">
                                <span class="badge {{ $r->conversion_rate >= 2 ? 'bg-success' : ($r->conversion_rate >= 0.5 ? 'bg-warning' : 'bg-secondary') }}">{{ $r->conversion_rate }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.analytics.overview.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var el = document.getElementById('geoMap');
    if (!el) return;

    // Simple built-in country centroids. Covers most common ones; unknowns are skipped silently.
    var CENTROIDS = {
        FR: [46.2276, 2.2137],  GB: [55.3781, -3.4360],  US: [37.0902, -95.7129],
        DE: [51.1657, 10.4515], ES: [40.4637, -3.7492],  IT: [41.8719, 12.5674],
        NL: [52.1326, 5.2913],  BE: [50.5039, 4.4699],   CH: [46.8182, 8.2275],
        CA: [56.1304, -106.346],AU: [-25.2744, 133.7751],JP: [36.2048, 138.2529],
        KH: [12.5657, 104.991], TH: [15.8700, 100.9925], VN: [14.0583, 108.2772],
        SG: [1.3521, 103.8198], CN: [35.8617, 104.1954], HK: [22.3193, 114.1694],
        KR: [35.9078, 127.7669],MY: [4.2105, 101.9758],  ID: [-0.7893, 113.9213],
        PH: [12.8797, 121.774], IN: [20.5937, 78.9629],  BR: [-14.2350, -51.9253],
        MX: [23.6345, -102.5528],RU: [61.5240, 105.3188],ZA: [-30.5595, 22.9375],
        AE: [23.4241, 53.8478], SA: [23.8859, 45.0792],  NZ: [-40.9006, 174.8860],
        IE: [53.4129, -8.2439], SE: [60.1282, 18.6435],  NO: [60.4720, 8.4689],
        DK: [56.2639, 9.5018],  FI: [61.9241, 25.7482],  PL: [51.9194, 19.1451],
        PT: [39.3999, -8.2245], AT: [47.5162, 14.5501],  CZ: [49.8175, 15.4730],
    };

    var rows = @json($rows);
    var maxSessions = 0;
    rows.forEach(function (r) { if (r.sessions > maxSessions) maxSessions = r.sessions; });

    var map = L.map(el, { worldCopyJump: true }).setView([20, 10], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 10,
    }).addTo(map);

    rows.forEach(function (r) {
        var c = CENTROIDS[r.country_code];
        if (!c) return;
        var radius = maxSessions > 0 ? 6 + 22 * Math.sqrt(r.sessions / maxSessions) : 8;
        L.circleMarker(c, {
            radius: radius,
            fillColor: '#3b82f6', color: '#1e40af',
            weight: 1.5, fillOpacity: 0.55,
        })
        .bindTooltip(r.country_code + ': ' + r.sessions + ' sessions · ' + r.orders + ' orders · ' + Number(r.revenue).toFixed(2) + '$')
        .addTo(map);
    });
})();
</script>
@endsection
