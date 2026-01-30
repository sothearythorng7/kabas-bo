@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bug"></i> POS Client Logs</h2>
        <div>
            <button class="btn btn-outline-secondary" id="btn-refresh">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button class="btn btn-outline-danger" id="btn-clear-old">
                <i class="bi bi-trash"></i> Clear Old Logs (> 7 days)
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Level</label>
                    <select class="form-select" id="filter-level">
                        <option value="">All</option>
                        <option value="critical">Critical</option>
                        <option value="error">Error</option>
                        <option value="warn">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Message Contains</label>
                    <input type="text" class="form-control" id="filter-message" placeholder="e.g., FREEZE, SYNC">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Session ID</label>
                    <select class="form-select" id="filter-session">
                        <option value="">All Sessions</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary d-block w-100" id="btn-apply-filters">
                        <i class="bi bi-funnel"></i> Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-bg-danger">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold" id="stat-critical">0</div>
                    <small>Critical</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-bg-warning">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold" id="stat-error">0</div>
                    <small>Errors</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-bg-info">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold" id="stat-warn">0</div>
                    <small>Warnings</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-bg-secondary">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold" id="stat-freeze">0</div>
                    <small>Freezes</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="fs-3 fw-bold" id="stat-total">{{ count($logs) }}</div>
                    <small>Total Logs</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0" id="logs-table">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th style="width: 80px;">Level</th>
                            <th style="width: 180px;">Time</th>
                            <th style="width: 200px;">Message</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $levelClass = match($log->level) {
                                'critical' => 'table-danger fw-bold',
                                'error' => 'table-warning',
                                'warn' => 'table-info',
                                'info' => '',
                                default => 'text-muted'
                            };
                            $sessionId = $log->context['device']['sessionId'] ?? ($log->context['session_id'] ?? 'N/A');
                        @endphp
                        <tr class="{{ $levelClass }}" data-level="{{ $log->level }}" data-session="{{ $sessionId }}">
                            <td>
                                <span class="badge bg-{{ $log->level === 'critical' ? 'danger' : ($log->level === 'error' ? 'warning text-dark' : ($log->level === 'warn' ? 'info' : ($log->level === 'info' ? 'secondary' : 'light text-dark'))) }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td class="small">
                                <div>{{ $log->created_at }}</div>
                                <div class="text-muted">{{ $log->client_timestamp }}</div>
                            </td>
                            <td class="fw-semibold">{{ $log->message }}</td>
                            <td class="small">
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleContext(this)">
                                    <i class="bi bi-chevron-down"></i> Details
                                </button>
                                <pre class="d-none mt-2 mb-0 p-2 bg-dark text-light rounded" style="font-size: 11px; max-height: 200px; overflow: auto;">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2">No logs yet. Logs will appear here when the POS is used.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #logs-table tbody tr {
        cursor: pointer;
    }
    #logs-table pre {
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>
@endpush

<script>
function toggleContext(btn) {
    const pre = btn.nextElementSibling;
    const icon = btn.querySelector('i');

    if (pre.classList.contains('d-none')) {
        pre.classList.remove('d-none');
        icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
    } else {
        pre.classList.add('d-none');
        icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const logs = @json($logs);

    // Calculate stats
    let stats = { critical: 0, error: 0, warn: 0, freeze: 0 };
    let sessions = new Set();

    logs.forEach(log => {
        if (log.level === 'critical') stats.critical++;
        if (log.level === 'error') stats.error++;
        if (log.level === 'warn') stats.warn++;
        if (log.message && log.message.includes('FREEZE')) stats.freeze++;

        const sid = log.context?.device?.sessionId || log.context?.session_id;
        if (sid) sessions.add(sid);
    });

    document.getElementById('stat-critical').textContent = stats.critical;
    document.getElementById('stat-error').textContent = stats.error;
    document.getElementById('stat-warn').textContent = stats.warn;
    document.getElementById('stat-freeze').textContent = stats.freeze;

    // Populate session filter
    const sessionSelect = document.getElementById('filter-session');
    sessions.forEach(sid => {
        const opt = document.createElement('option');
        opt.value = sid;
        opt.textContent = sid.substring(0, 20) + '...';
        sessionSelect.appendChild(opt);
    });

    // Filter functionality
    document.getElementById('btn-apply-filters').addEventListener('click', applyFilters);
    document.getElementById('filter-level').addEventListener('change', applyFilters);
    document.getElementById('filter-message').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') applyFilters();
    });

    function applyFilters() {
        const level = document.getElementById('filter-level').value.toLowerCase();
        const message = document.getElementById('filter-message').value.toLowerCase();
        const session = document.getElementById('filter-session').value;

        document.querySelectorAll('#logs-table tbody tr').forEach(row => {
            const rowLevel = row.dataset.level;
            const rowSession = row.dataset.session;
            const rowMessage = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';

            let show = true;

            if (level && rowLevel !== level) show = false;
            if (message && !rowMessage.includes(message)) show = false;
            if (session && rowSession !== session) show = false;

            row.style.display = show ? '' : 'none';
        });
    }

    // Refresh
    document.getElementById('btn-refresh').addEventListener('click', () => location.reload());

    // Clear old logs
    document.getElementById('btn-clear-old').addEventListener('click', async () => {
        if (!confirm('Delete all logs older than 7 days?')) return;

        try {
            const response = await fetch('{{ route("pos.logs.clear") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            alert(`Deleted ${data.deleted} old logs`);
            location.reload();
        } catch (e) {
            alert('Error clearing logs: ' + e.message);
        }
    });
});
</script>
@endsection
