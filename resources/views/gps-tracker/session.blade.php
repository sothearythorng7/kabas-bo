@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-key"></i> GPS Tracker - Session</h1>
    <a href="{{ route('gps-tracker.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Map
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        @if(!empty($config['session_expired']))
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Session expired!</strong> Last working: {{ $config['expired_at'] ?? 'unknown' }}.
                Please update the cookies below.
            </div>
        @else
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                Session active. Last login: {{ $config['last_login'] ?? 'unknown' }}
            </div>
        @endif

        <div class="card shadow">
            <div class="card-header"><h6 class="mb-0">Update TKSTAR Session Cookies</h6></div>
            <div class="card-body">
                <form action="{{ route('gps-tracker.session.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Cookies</label>
                        <textarea name="cookies" class="form-control font-monospace" rows="4" required>{{ $config['cookies'] ?? '' }}</textarea>
                        @error('cookies')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save Session</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-question-circle"></i> How to get cookies</h6></div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Go to <a href="https://www.mytkstar.net" target="_blank">mytkstar.net</a></li>
                    <li>Login with IMEI + password</li>
                    <li>Open DevTools (F12)</li>
                    <li>Go to <strong>Network</strong> tab</li>
                    <li>Click any XHR request</li>
                    <li>Copy the full <strong>Cookie</strong> header value</li>
                    <li>Paste it here</li>
                </ol>
            </div>
        </div>

        <div class="card shadow mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Current Config</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>User ID:</strong> <code>{{ $config['user_id'] ?? '-' }}</code></p>
                <p class="mb-1"><strong>Device ID:</strong> <code>{{ $config['device_id'] ?? '-' }}</code></p>
                <p class="mb-0"><strong>Last login:</strong> {{ $config['last_login'] ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
