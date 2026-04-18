@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><i class="bi bi-cpu"></i> GPS Devices</h1>
    <a href="{{ route('gps-tracker.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Map
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Device ID</th>
                            <th>Name</th>
                            <th>Model</th>
                            <th>SIM</th>
                            <th>Positions</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($devices as $device)
                        <tr>
                            <td><code>{{ $device->device_id }}</code></td>
                            <td>{{ $device->name }}</td>
                            <td>{{ $device->model ?? '-' }}</td>
                            <td>{{ $device->sim_number ?? '-' }}</td>
                            <td>{{ $device->positions_count }}</td>
                            <td>
                                @if($device->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('gps-tracker.devices.destroy', $device) }}" method="POST"
                                      onsubmit="return confirm('Delete this device and all its positions?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">No devices yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header"><h6 class="mb-0">Add Device</h6></div>
            <div class="card-body">
                <form action="{{ route('gps-tracker.devices.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Device ID <small class="text-muted">(from tracker label)</small></label>
                        <input type="text" name="device_id" class="form-control" required
                               placeholder="e.g. 0123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g. Car TK905">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control"
                               placeholder="e.g. TK905-4G">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">SIM Number</label>
                        <input type="text" name="sim_number" class="form-control"
                               placeholder="e.g. 076459260">
                    </div>
                    <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Device</button>
                </form>
            </div>
        </div>

        <div class="card shadow mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-info-circle"></i> Server Info</h6></div>
            <div class="card-body">
                <p class="mb-1"><strong>Server:</strong> <code>kabasconceptstore.com</code></p>
                <p class="mb-1"><strong>Port:</strong> <code>5023</code></p>
                <p class="mb-0"><small class="text-muted">Configure your tracker to send data to this address and port (GT06 protocol).</small></p>
            </div>
        </div>
    </div>
</div>
@endsection
