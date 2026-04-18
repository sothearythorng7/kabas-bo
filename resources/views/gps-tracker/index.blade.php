@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #gps-map { height: 600px; width: 100%; border-radius: 8px; }
    .device-card { cursor: pointer; transition: background-color 0.2s; }
    .device-card:hover { background-color: #f0f0f0; }
    .device-card.active { border-color: #0d6efd !important; background-color: #e8f0fe; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .status-dot.online { background-color: #28a745; }
    .status-dot.offline { background-color: #dc3545; }
    .stop-tooltip { background: #ff8c00; color: #fff; border: none; font-weight: bold; font-size: 11px; padding: 2px 6px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
    .stop-tooltip::before { border-top-color: #ff8c00 !important; }
    .summary-card { border-left: 4px solid; }
    .summary-card.drive { border-left-color: #0d6efd; }
    .summary-card.stop { border-left-color: #ff8c00; }
    .summary-card.distance { border-left-color: #28a745; }
    .summary-card.time { border-left-color: #6f42c1; }
    .quick-date { font-size: 0.8rem; }
    .trip-row { cursor: pointer; transition: background-color 0.15s; }
    .trip-row:hover { background-color: #e8f0fe !important; }
    .trip-row.type-stop { background-color: #fff8f0; }
    #trip-loading { display: none; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-geo-alt"></i> GPS Tracker</h1>
    <div>
        @if($listenerRunning)
            <span class="badge bg-success me-2"><i class="bi bi-broadcast"></i> Listener actif</span>
        @else
            <span class="badge bg-danger me-2"><i class="bi bi-exclamation-triangle"></i> Listener inactif</span>
        @endif
        <a href="{{ route('gps-tracker.devices') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-cpu"></i> Devices
        </a>
    </div>
</div>

<div class="row">
    <!-- Map -->
    <div class="col-lg-9 mb-3">
        <div class="card shadow">
            <div class="card-body p-0">
                <div id="gps-map"></div>
            </div>
        </div>

        <!-- History controls -->
        <div class="card shadow mt-3">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="card-title mb-0"><i class="bi bi-clock-history"></i> Historique</h6>
                    <div class="quick-date">
                        <button class="btn btn-outline-secondary btn-sm" data-quick="today">Aujourd'hui</button>
                        <button class="btn btn-outline-secondary btn-sm" data-quick="yesterday">Hier</button>
                        <button class="btn btn-outline-secondary btn-sm" data-quick="week">7 jours</button>
                    </div>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Device</label>
                        <select id="history-device" class="form-select form-select-sm">
                            @foreach($devices as $device)
                                <option value="{{ $device->device_id }}" @if($devices->count() === 1) selected @endif>{{ $device->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">De</label>
                        <input type="datetime-local" id="history-from" class="form-control form-control-sm"
                               value="{{ now()->startOfDay()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">A</label>
                        <input type="datetime-local" id="history-to" class="form-control form-control-sm"
                               value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Arret min.</label>
                        <select id="stop-threshold" class="form-select form-select-sm">
                            <option value="3">3 min</option>
                            <option value="5" selected>5 min</option>
                            <option value="10">10 min</option>
                            <option value="15">15 min</option>
                            <option value="30">30 min</option>
                            <option value="60">1 heure</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="show-stops" checked>
                            <label class="form-check-label small" for="show-stops">Arrets</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button id="btn-history" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day summary -->
        <div id="day-summary" class="d-none mt-3">
            <div class="row g-2">
                <div class="col-3">
                    <div class="card summary-card distance p-2 text-center">
                        <div class="text-muted small">Distance</div>
                        <div class="fw-bold fs-5" id="sum-distance">-</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card summary-card drive p-2 text-center">
                        <div class="text-muted small"><i class="bi bi-car-front"></i> Conduite</div>
                        <div class="fw-bold fs-5" id="sum-drive">-</div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card summary-card stop p-2 text-center">
                        <div class="text-muted small"><i class="bi bi-pause-circle"></i> Arrets</div>
                        <div class="fw-bold fs-5" id="sum-stop">-</div>
                        <div class="text-muted small" id="sum-stop-count"></div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card summary-card time p-2 text-center">
                        <div class="text-muted small"><i class="bi bi-clock"></i> Periode</div>
                        <div class="fw-bold" id="sum-period">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="trip-loading" class="text-center py-3">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="text-muted mt-1 small">Chargement de l'historique...</div>
        </div>

        <!-- Trip steps table -->
        <div id="trip-steps-card" class="card shadow mt-3 d-none">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:35px">#</th>
                                <th style="width:80px">Type</th>
                                <th>Lieu</th>
                                <th style="width:65px">Debut</th>
                                <th style="width:65px">Fin</th>
                                <th style="width:75px">Duree</th>
                                <th style="width:70px" class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody id="trip-steps-body"></tbody>
                        <tfoot id="trip-steps-foot" class="table-light fw-bold"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Device list -->
    <div class="col-lg-3">
        <div class="card shadow">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-broadcast"></i> Devices</h6></div>
            <div class="card-body p-0">
                @forelse($devices as $device)
                    @php
                        $pos = $device->latestPosition;
                        $isOnline = $pos && $pos->created_at->diffInMinutes(now()) < 10;
                    @endphp
                    <div class="device-card border-bottom p-3" data-device-id="{{ $device->device_id }}"
                         @if($pos) data-lat="{{ $pos->latitude }}" data-lng="{{ $pos->longitude }}" @endif>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>{{ $device->name }}</strong>
                            <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}"
                                  title="{{ $isOnline ? 'En ligne' : 'Hors ligne' }}"></span>
                        </div>
                        @if($pos)
                            <small class="text-muted d-block">
                                <i class="bi bi-speedometer2"></i> {{ round($pos->speed ?? 0) }} km/h
                                @if($pos->acc_on !== null)
                                    | <i class="bi bi-key{{ $pos->acc_on ? '-fill' : '' }}"></i> {{ $pos->acc_on ? 'ON' : 'OFF' }}
                                @endif
                                @if($pos->battery_level)
                                    | <i class="bi bi-battery-half"></i> {{ $pos->battery_level }}%
                                @endif
                            </small>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> {{ $pos->device_time ? $pos->device_time->diffForHumans() : $pos->created_at->diffForHumans() }}
                            </small>
                        @else
                            <small class="text-muted">Pas de position</small>
                        @endif
                    </div>
                @empty
                    <div class="p-3 text-muted text-center">
                        Aucun device.<br>
                        <a href="{{ route('gps-tracker.devices') }}">Ajouter un device</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('gps-map').setView([11.5564, 104.9282], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OSM',
        maxZoom: 19
    }).addTo(map);

    const markers = {};
    let historyLayers = [];

    // ---- Device markers ----
    document.querySelectorAll('.device-card[data-lat]').forEach(card => {
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);
        const id = card.dataset.deviceId;
        const name = card.querySelector('strong').textContent;

        const marker = L.marker([lat, lng]).addTo(map).bindPopup('<b>' + name + '</b><br>ID: ' + id);
        markers[id] = marker;

        card.addEventListener('click', () => {
            map.setView([lat, lng], 16);
            marker.openPopup();
            document.querySelectorAll('.device-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            // Auto-select device in history
            document.getElementById('history-device').value = id;
        });
    });

    const markerKeys = Object.keys(markers);
    if (markerKeys.length > 0) {
        map.fitBounds(L.featureGroup(Object.values(markers)).getBounds().pad(0.1));
    }

    // ---- Quick date buttons ----
    document.querySelectorAll('[data-quick]').forEach(btn => {
        btn.addEventListener('click', () => {
            const now = new Date();
            let from, to;
            switch (btn.dataset.quick) {
                case 'today':
                    from = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0);
                    to = now;
                    break;
                case 'yesterday':
                    from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 0, 0);
                    to = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1, 23, 59);
                    break;
                case 'week':
                    from = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 7, 0, 0);
                    to = now;
                    break;
            }
            const fmt = d => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + 'T' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
            document.getElementById('history-from').value = fmt(from);
            document.getElementById('history-to').value = fmt(to);
            // Auto-trigger search
            document.getElementById('btn-history').click();
        });
    });

    // ---- Helpers ----
    function haversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const toRad = x => x * Math.PI / 180;
        const dLat = toRad(lat2 - lat1), dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat/2)**2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function formatDuration(minutes) {
        if (minutes < 1) return '<1 min';
        if (minutes < 60) return minutes + ' min';
        const h = Math.floor(minutes / 60), m = minutes % 60;
        return h + 'h' + (m > 0 ? String(m).padStart(2, '0') : '');
    }

    function formatTime(date) {
        return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }

    // ---- Geocoding (server-side: Overpass POIs + Nominatim + DB cache) ----
    const geocodeMemoryCache = {};

    async function batchGeocode(points) {
        // Deduplicate
        const unique = {};
        points.forEach(p => {
            const key = p.lat.toFixed(2) + ',' + p.lng.toFixed(2);
            if (!geocodeMemoryCache[key]) unique[key] = p;
        });

        const toRequest = Object.values(unique);
        if (!toRequest.length) return;

        try {
            const resp = await fetch('{{ route("gps-tracker.geocode") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ points: toRequest })
            });
            const data = await resp.json();
            for (const [key, name] of Object.entries(data.results || {})) {
                geocodeMemoryCache[key] = name;
            }
        } catch (e) {
            console.warn('Geocode failed:', e);
        }
    }

    function getGeoName(lat, lng) {
        return geocodeMemoryCache[lat.toFixed(2) + ',' + lng.toFixed(2)] || null;
    }

    // ---- Movement filtering ----
    function filterMovingPoints(positions) {
        return positions;
    }


    // Haversine distance for a series of positions (fallback when not using OSRM)
    function rawDistanceKm(positions) {
        let dist = 0;
        for (let i = 1; i < positions.length; i++) {
            dist += haversineMeters(
                parseFloat(positions[i-1].latitude), parseFloat(positions[i-1].longitude),
                parseFloat(positions[i].latitude), parseFloat(positions[i].longitude)
            );
        }
        return dist / 1000;
    }

    // ---- Stop detection with ACC support ----
    function detectStops(positions, minMinutes) {
        const RADIUS = 200;
        const SPEED_MAX = 10;
        const MERGE_DIST = 300;

        // Pass 1: detect raw stop clusters using rolling centroid + ACC
        const rawStops = [];
        let i = 0;
        while (i < positions.length) {
            const p = positions[i];
            const speed = parseFloat(p.speed) || 0;
            const accOff = p.acc_on === false || p.acc_on === 0;

            // A point is "stopped" if ACC is off, OR if speed is low
            if (accOff || speed < SPEED_MAX) {
                let centLat = parseFloat(p.latitude);
                let centLng = parseFloat(p.longitude);
                let count = 1;
                let hasAccOff = accOff;
                let j = i + 1;

                while (j < positions.length) {
                    const q = positions[j];
                    const qLat = parseFloat(q.latitude);
                    const qLng = parseFloat(q.longitude);
                    const spd = parseFloat(q.speed) || 0;
                    const qAccOff = q.acc_on === false || q.acc_on === 0;
                    const dist = haversineMeters(centLat, centLng, qLat, qLng);

                    if (dist < RADIUS && (qAccOff || spd < SPEED_MAX)) {
                        centLat = (centLat * count + qLat) / (count + 1);
                        centLng = (centLng * count + qLng) / (count + 1);
                        count++;
                        if (qAccOff) hasAccOff = true;
                        j++;
                    } else {
                        break;
                    }
                }

                const first = positions[i];
                const last = positions[j - 1];
                const t1 = new Date(first.device_time || first.created_at);
                const t2 = new Date(last.device_time || last.created_at);
                rawStops.push({
                    lat: centLat, lng: centLng,
                    startTime: t1, endTime: t2,
                    durationMin: (t2 - t1) / 60000,
                    startIndex: i, endIndex: j - 1, count,
                    engineOff: hasAccOff
                });
                i = j;
            } else {
                i++;
            }
        }

        // Pass 2: merge nearby stops
        const merged = [];
        for (const stop of rawStops) {
            if (merged.length > 0) {
                const prev = merged[merged.length - 1];
                const dist = haversineMeters(prev.lat, prev.lng, stop.lat, stop.lng);
                let maxSpeedBetween = 0, maxDistBetween = 0;
                for (let k = prev.endIndex + 1; k < stop.startIndex; k++) {
                    maxSpeedBetween = Math.max(maxSpeedBetween, parseFloat(positions[k].speed) || 0);
                    maxDistBetween = Math.max(maxDistBetween, haversineMeters(prev.lat, prev.lng, parseFloat(positions[k].latitude), parseFloat(positions[k].longitude)));
                }
                // Merge if nobody actually moved between the two stops (GPS drift)
                const noPointsBetween = (stop.startIndex - prev.endIndex <= 1);
                if (noPointsBetween || maxSpeedBetween < 3) {
                    const totalCount = prev.count + stop.count;
                    prev.lat = (prev.lat * prev.count + stop.lat * stop.count) / totalCount;
                    prev.lng = (prev.lng * prev.count + stop.lng * stop.count) / totalCount;
                    prev.endTime = stop.endTime;
                    prev.endIndex = stop.endIndex;
                    prev.count = totalCount;
                    prev.durationMin = (prev.endTime - prev.startTime) / 60000;
                    if (stop.engineOff) prev.engineOff = true;
                    continue;
                }
            }
            merged.push({...stop});
        }

        // Pass 3: filter by minimum duration
        return merged
            .filter(s => s.durationMin >= minMinutes)
            .map(s => ({
                lat: s.lat, lng: s.lng,
                startTime: s.startTime, endTime: s.endTime,
                durationMin: Math.round(s.durationMin),
                startIndex: s.startIndex, endIndex: s.endIndex,
                engineOff: s.engineOff
            }));
    }

    // ---- Split positions into segments by stops ----
    function splitSegments(positions, stops) {
        if (!stops.length) return [positions];
        const segments = [];
        let cursor = 0;
        for (const stop of stops) {
            if (stop.startIndex >= cursor) segments.push(positions.slice(cursor, stop.startIndex + 1));
            cursor = stop.endIndex;
        }
        if (cursor < positions.length) segments.push(positions.slice(cursor));
        return segments;
    }

    // ---- Build trip steps ----
    function buildTripSteps(positions, stops, segmentDistances) {
        const steps = [];
        let cursor = 0, segIdx = 0;

        for (const stop of stops) {
            if (stop.startIndex > cursor) {
                const drivePos = positions.slice(cursor, stop.startIndex + 1);
                const t1 = new Date(drivePos[0].device_time || drivePos[0].created_at);
                const t2 = new Date(drivePos[drivePos.length - 1].device_time || drivePos[drivePos.length - 1].created_at);
                const dist = segmentDistances[segIdx] !== undefined ? segmentDistances[segIdx] : rawDistanceKm(drivePos);
                steps.push({
                    type: 'drive', startTime: t1, endTime: t2,
                    durationMin: Math.round((t2 - t1) / 60000),
                    distanceKm: dist,
                    lat: parseFloat(drivePos[Math.floor(drivePos.length / 2)].latitude),
                    lng: parseFloat(drivePos[Math.floor(drivePos.length / 2)].longitude),
                });
                segIdx++;
            }
            steps.push({
                type: 'stop', startTime: stop.startTime, endTime: stop.endTime,
                durationMin: stop.durationMin, lat: stop.lat, lng: stop.lng,
                engineOff: stop.engineOff
            });
            cursor = stop.endIndex;
        }

        // Trailing drive
        if (cursor < positions.length - 1) {
            const drivePos = positions.slice(cursor);
            const t1 = new Date(drivePos[0].device_time || drivePos[0].created_at);
            const t2 = new Date(drivePos[drivePos.length - 1].device_time || drivePos[drivePos.length - 1].created_at);
            const dist = segmentDistances[segIdx] !== undefined ? segmentDistances[segIdx] : rawDistanceKm(drivePos);
            if (drivePos.length >= 2) {
                steps.push({
                    type: 'drive', startTime: t1, endTime: t2,
                    durationMin: Math.round((t2 - t1) / 60000),
                    distanceKm: dist,
                    lat: parseFloat(drivePos[Math.floor(drivePos.length / 2)].latitude),
                    lng: parseFloat(drivePos[Math.floor(drivePos.length / 2)].longitude),
                });
            }
        }

        return steps;
    }

    function clearHistory() {
        historyLayers.forEach(l => map.removeLayer(l));
        historyLayers = [];
        document.getElementById('trip-steps-card').classList.add('d-none');
        document.getElementById('day-summary').classList.add('d-none');
    }

    const segmentColors = ['#0d6efd', '#e35d00', '#6f42c1', '#20c997', '#d63384', '#0dcaf0', '#6610f2', '#fd7e14'];

    // ---- Render trip table ----
    function renderTripTable(steps, stopMarkers) {
        const card = document.getElementById('trip-steps-card');
        const tbody = document.getElementById('trip-steps-body');
        const tfoot = document.getElementById('trip-steps-foot');
        tbody.innerHTML = '';
        tfoot.innerHTML = '';

        if (!steps.length) { card.classList.add('d-none'); return; }
        card.classList.remove('d-none');

        let totalDrive = 0, totalStop = 0, totalDist = 0, stopCount = 0;

        steps.forEach((step, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'trip-row' + (step.type === 'stop' ? ' type-stop' : '');
            tr.addEventListener('click', () => map.setView([step.lat, step.lng], 16));

            if (step.type === 'stop') {
                totalStop += step.durationMin;
                stopCount++;
                const locName = getGeoName(step.lat, step.lng);
                const engineIcon = step.engineOff ? '<i class="bi bi-key text-danger" title="Moteur coupe"></i> ' : '';
                tr.innerHTML =
                    '<td class="text-muted small">' + (idx + 1) + '</td>'
                    + '<td><span class="badge bg-warning text-dark"><i class="bi bi-pause-circle"></i> Arret</span></td>'
                    + '<td>' + engineIcon + (locName || '<span class="text-muted"><i class="bi bi-hourglass-split"></i></span>') + '</td>'
                    + '<td class="small">' + formatTime(step.startTime) + '</td>'
                    + '<td class="small">' + formatTime(step.endTime) + '</td>'
                    + '<td><strong>' + formatDuration(step.durationMin) + '</strong></td>'
                    + '<td class="text-end"><small class="text-muted"><i class="bi bi-geo-alt"></i></small></td>';

                // Update stop marker popup with location name
                if (locName && stopMarkers && stopMarkers[step.stopIdx] !== undefined) {
                    const marker = stopMarkers[step.stopIdx];
                    marker.setPopupContent(
                        '<div style="text-align:center;min-width:160px">'
                        + '<b><i class="bi bi-pause-circle"></i> Arret #' + (step.stopIdx + 1) + '</b><br>'
                        + '<span style="font-size:0.85em">' + locName + '</span><br>'
                        + (step.engineOff ? '<span class="text-danger" style="font-size:0.8em"><i class="bi bi-key"></i> Moteur coupe</span><br>' : '')
                        + '<span style="font-size:1.3em;font-weight:bold;color:#e65100">' + formatDuration(step.durationMin) + '</span><br>'
                        + '<small>' + formatTime(step.startTime) + ' - ' + formatTime(step.endTime) + '</small>'
                        + '</div>'
                    );
                }
            } else {
                totalDrive += step.durationMin;
                totalDist += step.distanceKm;
                const avgSpeed = step.durationMin > 0 ? Math.round(step.distanceKm / (step.durationMin / 60)) : 0;
                tr.innerHTML =
                    '<td class="text-muted small">' + (idx + 1) + '</td>'
                    + '<td><span class="badge bg-primary"><i class="bi bi-car-front"></i> Trajet</span></td>'
                    + '<td class="text-muted small">' + step.distanceKm.toFixed(1) + ' km' + (avgSpeed > 0 ? ' &middot; ~' + avgSpeed + ' km/h moy.' : '') + '</td>'
                    + '<td class="small">' + formatTime(step.startTime) + '</td>'
                    + '<td class="small">' + formatTime(step.endTime) + '</td>'
                    + '<td>' + formatDuration(step.durationMin) + '</td>'
                    + '<td></td>';
            }
            tbody.appendChild(tr);
        });

        tfoot.innerHTML =
            '<tr><td colspan="5" class="small">Total: ' + totalDist.toFixed(1) + ' km</td>'
            + '<td>' + formatDuration(totalDrive + totalStop) + '</td>'
            + '<td class="text-end small">' + formatDuration(totalDrive) + ' / ' + formatDuration(totalStop) + '</td></tr>';

        // Update summary cards
        document.getElementById('sum-distance').textContent = totalDist.toFixed(1) + ' km';
        document.getElementById('sum-drive').textContent = formatDuration(totalDrive);
        document.getElementById('sum-stop').textContent = formatDuration(totalStop);
        document.getElementById('sum-stop-count').textContent = stopCount + ' arret' + (stopCount > 1 ? 's' : '');
        if (steps.length > 0) {
            const firstTime = steps[0].startTime;
            const lastTime = steps[steps.length - 1].endTime;
            document.getElementById('sum-period').textContent = formatTime(firstTime) + ' - ' + formatTime(lastTime);
        }
        document.getElementById('day-summary').classList.remove('d-none');
    }

    // ---- Main: Load History (progressive rendering) ----
    document.getElementById('btn-history').addEventListener('click', async function() {
        const deviceId = document.getElementById('history-device').value;
        const from = document.getElementById('history-from').value;
        const to = document.getElementById('history-to').value;
        const showStops = document.getElementById('show-stops').checked;
        const stopThreshold = parseInt(document.getElementById('stop-threshold').value) || 5;

        if (!deviceId) { alert('Selectionnez un device'); return; }

        clearHistory();
        this.disabled = true;
        document.getElementById('trip-loading').style.display = 'block';

        try {
            // Step 1: Fetch positions
            const resp = await fetch('{{ route("gps-tracker.history") }}?device_id=' + deviceId + '&from=' + from + '&to=' + to);
            const positions = await resp.json();

            if (!positions.length) {
                alert('Aucune donnee pour cette periode');
                return;
            }

            // Step 2: Detect stops
            const stops = showStops ? detectStops(positions, stopThreshold) : [];

            // Step 3: Draw colored polylines — color changes at each stop
            // Build a color index per position: segment 0 before first stop, 1 after first stop, etc.
            const posColors = new Array(positions.length).fill(0);
            let colorIdx = 0;
            for (const stop of stops) {
                for (let i = stop.startIndex; i <= stop.endIndex && i < positions.length; i++) {
                    posColors[i] = colorIdx; // stop points keep previous segment color
                }
                colorIdx++;
                for (let i = stop.endIndex + 1; i < positions.length; i++) {
                    posColors[i] = colorIdx;
                }
            }

            // Group consecutive positions by color into segments, overlapping by 1 point for continuity
            let boundsCoords = [];
            const segments = [];
            let segStart = 0;
            for (let i = 1; i < positions.length; i++) {
                if (posColors[i] !== posColors[i - 1]) {
                    segments.push({ from: segStart, to: i, color: posColors[segStart] });
                    segStart = i;
                }
            }
            segments.push({ from: segStart, to: positions.length - 1, color: posColors[segStart] });

            const segmentDistancesRaw = [];
            for (const seg of segments) {
                // Include one point before for continuity (overlap)
                const from = Math.max(0, seg.from - 1);
                const slice = positions.slice(from, seg.to + 1);
                if (slice.length < 2) { segmentDistancesRaw.push(0); continue; }
                const color = segmentColors[seg.color % segmentColors.length];
                const coords = slice.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);
                segmentDistancesRaw.push(rawDistanceKm(slice));
                const outline = L.polyline(coords, { color: '#fff', weight: 6, opacity: 0.4 }).addTo(map);
                const line = L.polyline(coords, { color, weight: 3, opacity: 0.9 }).addTo(map);
                historyLayers.push(outline);
                historyLayers.push(line);
                boundsCoords = boundsCoords.concat(coords);
            }

            if (boundsCoords.length >= 2) {
                map.fitBounds(L.latLngBounds(boundsCoords).pad(0.1));
            }

            // Stop markers (no geocode yet — just coordinates)
            const stopMarkerMap = {};
            stops.forEach((stop, idx) => {
                const marker = L.circleMarker([stop.lat, stop.lng], {
                    radius: 10, color: '#ff8c00', fillColor: '#ff8c00', fillOpacity: 0.85, weight: 2
                }).addTo(map);

                marker.bindPopup(
                    '<div style="text-align:center;min-width:160px">'
                    + '<b><i class="bi bi-pause-circle"></i> Arret #' + (idx + 1) + '</b><br>'
                    + (stop.engineOff ? '<span class="text-danger" style="font-size:0.8em"><i class="bi bi-key"></i> Moteur coupe</span><br>' : '')
                    + '<span style="font-size:1.3em;font-weight:bold;color:#e65100">' + formatDuration(stop.durationMin) + '</span><br>'
                    + '<small>' + formatTime(stop.startTime) + ' - ' + formatTime(stop.endTime) + '</small>'
                    + '</div>'
                );

                marker.bindTooltip(formatDuration(stop.durationMin), {
                    permanent: true, direction: 'top', className: 'stop-tooltip', offset: [0, -10]
                });

                stopMarkerMap[idx] = marker;
                historyLayers.push(marker);
            });

            // Start marker (green)
            const startPos = positions[0];
            const startM = L.circleMarker([startPos.latitude, startPos.longitude], {
                radius: 8, color: '#28a745', fillColor: '#28a745', fillOpacity: 1
            }).addTo(map).bindPopup('<b>Depart</b><br>' + formatTime(new Date(startPos.device_time || startPos.created_at)));
            historyLayers.push(startM);

            // End marker (red)
            const endPos = positions[positions.length - 1];
            const endM = L.circleMarker([endPos.latitude, endPos.longitude], {
                radius: 8, color: '#dc3545', fillColor: '#dc3545', fillOpacity: 1
            }).addTo(map).bindPopup('<b>Arrivee</b><br>' + formatTime(new Date(endPos.device_time || endPos.created_at)));
            historyLayers.push(endM);

            // Build & render table immediately with raw distances
            const tripSteps = buildTripSteps(positions, stops, segmentDistancesRaw);
            let stopCounter = 0;
            tripSteps.forEach(step => { if (step.type === 'stop') step.stopIdx = stopCounter++; });
            renderTripTable(tripSteps, stopMarkerMap);

            // Step 4: BACKGROUND — geocode stop locations
            if (stops.length > 0) {
                (async () => {
                    await batchGeocode(stops.map(s => ({ lat: s.lat, lng: s.lng })));
                    // Update table cells and map popups with location names
                    const tbody = document.getElementById('trip-steps-body');
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach((tr, idx) => {
                        const step = tripSteps[idx];
                        if (!step || step.type !== 'stop') return;
                        const locName = getGeoName(step.lat, step.lng);
                        if (!locName) return;

                        // Update table cell (3rd column)
                        const cells = tr.querySelectorAll('td');
                        if (cells[2]) {
                            const engineIcon = step.engineOff ? '<i class="bi bi-key text-danger" title="Moteur coupe"></i> ' : '';
                            cells[2].innerHTML = engineIcon + locName;
                        }

                        // Update map popup
                        const marker = stopMarkerMap[step.stopIdx];
                        if (marker) {
                            marker.setPopupContent(
                                '<div style="text-align:center;min-width:160px">'
                                + '<b><i class="bi bi-pause-circle"></i> Arret #' + (step.stopIdx + 1) + '</b><br>'
                                + '<span style="font-size:0.85em">' + locName + '</span><br>'
                                + (step.engineOff ? '<span class="text-danger" style="font-size:0.8em"><i class="bi bi-key"></i> Moteur coupe</span><br>' : '')
                                + '<span style="font-size:1.3em;font-weight:bold;color:#e65100">' + formatDuration(step.durationMin) + '</span><br>'
                                + '<small>' + formatTime(step.startTime) + ' - ' + formatTime(step.endTime) + '</small>'
                                + '</div>'
                            );
                        }
                    });
                })();
            }

        } catch (e) {
            console.error('History error:', e);
            alert('Erreur lors du chargement');
        } finally {
            this.disabled = false;
            document.getElementById('trip-loading').style.display = 'none';
        }
    });

    // ---- Auto-refresh position every 30s ----
    setInterval(() => {
        fetch('{{ route("gps-tracker.latest") }}')
            .then(r => r.json())
            .then(devices => {
                devices.forEach(d => {
                    if (!d.position) return;
                    const latlng = [d.position.lat, d.position.lng];
                    if (markers[d.device_id]) {
                        markers[d.device_id].setLatLng(latlng);
                    } else {
                        markers[d.device_id] = L.marker(latlng).addTo(map).bindPopup('<b>' + d.name + '</b>');
                    }
                });
            });
    }, 30000);

    // ---- Auto-load today on page load if only 1 device ----
    @if($devices->count() === 1)
    setTimeout(() => document.getElementById('btn-history').click(), 500);
    @endif
});
</script>
@endpush
