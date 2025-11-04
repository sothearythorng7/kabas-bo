@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-hdd-stack"></i> {{ __('messages.backup.title') }}</h1>
        <form action="{{ route('backups.create') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('{{ __('messages.backup.create_confirm') }}')">
                <i class="bi bi-plus-circle"></i> {{ __('messages.backup.create_new') }}
            </button>
        </form>
    </div>

    @if(count($backups) === 0)
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> {{ __('messages.backup.no_backups') }}
        </div>
    @else
        <div class="card">
            <div class="card-header bg-light">
                <div class="row">
                    <div class="col-md-6">
                        <strong>{{ __('messages.backup.statistics') }}</strong>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <i class="bi bi-archive fs-3 text-primary"></i>
                            <h3 class="mt-2 mb-0">{{ count($backups) }}</h3>
                            <small class="text-muted">{{ __('messages.backup.total_backups') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <i class="bi bi-clock-history fs-3 text-success"></i>
                            <h3 class="mt-2 mb-0">{{ $backups[0]['date'] ?? '-' }}</h3>
                            <small class="text-muted">{{ __('messages.backup.last_backup') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            <i class="bi bi-file-earmark-zip fs-3 text-warning"></i>
                            <h3 class="mt-2 mb-0">{{ $backups[0]['size_human'] ?? '-' }}</h3>
                            <small class="text-muted">{{ __('messages.backup.last_size') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 border rounded">
                            @php
                                $totalSize = array_sum(array_column($backups, 'size'));
                                $totalSizeHuman = 0;
                                $units = ['B', 'KB', 'MB', 'GB'];
                                for ($i = 0; $totalSize > 1024 && $i < count($units) - 1; $i++) {
                                    $totalSize /= 1024;
                                }
                                $totalSizeHuman = round($totalSize, 2) . ' ' . $units[$i];
                            @endphp
                            <i class="bi bi-hdd fs-3 text-info"></i>
                            <h3 class="mt-2 mb-0">{{ $totalSizeHuman }}</h3>
                            <small class="text-muted">{{ __('messages.backup.total_size') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-light">
                <strong><i class="bi bi-list-ul"></i> {{ __('messages.backup.list') }}</strong>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.backup.filename') }}</th>
                            <th>{{ __('messages.backup.date_time') }}</th>
                            <th>{{ __('messages.backup.age') }}</th>
                            <th>{{ __('messages.backup.size') }}</th>
                            <th class="text-end">{{ __('messages.backup.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                            @php
                                $now = time();
                                $ageSeconds = $now - $backup['timestamp'];
                                $ageHours = floor($ageSeconds / 3600);
                                $ageMinutes = floor(($ageSeconds % 3600) / 60);

                                if ($ageHours > 0) {
                                    $ageDisplay = $ageHours . 'h ' . $ageMinutes . 'min';
                                } else {
                                    $ageDisplay = $ageMinutes . 'min';
                                }

                                // Badge de couleur selon l'âge
                                $ageBadgeClass = 'success';
                                if ($ageHours >= 3) {
                                    $ageBadgeClass = 'warning';
                                }
                                if ($ageHours >= 24) {
                                    $ageBadgeClass = 'secondary';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <code>{{ $backup['filename'] }}</code>
                                </td>
                                <td>
                                    <i class="bi bi-calendar3"></i> {{ $backup['date'] }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ageBadgeClass }}">
                                        <i class="bi bi-clock"></i> {{ $ageDisplay }}
                                    </span>
                                </td>
                                <td>
                                    <i class="bi bi-file-earmark-zip"></i> {{ $backup['size_human'] }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('backups.download', $backup['filename']) }}"
                                           class="btn btn-outline-primary"
                                           title="{{ __('messages.backup.download') }}">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <form action="{{ route('backups.delete', $backup['filename']) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('{{ __('messages.backup.delete_confirm') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-outline-danger"
                                                    title="{{ __('messages.backup.delete') }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <i class="bi bi-info-circle"></i>
            <strong>{{ __('messages.backup.info_title') }}</strong><br>
            <ul class="mb-0 mt-2">
                <li>{{ __('messages.backup.info_automatic') }}</li>
                <li>{{ __('messages.backup.info_retention') }}</li>
                <li>{{ __('messages.backup.info_no_downtime') }}</li>
            </ul>
        </div>
    @endif
</div>
@endsection
