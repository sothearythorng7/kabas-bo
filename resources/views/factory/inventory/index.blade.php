@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-clipboard-check"></i> {{ __('messages.factory.inventory.title') }}</h1>

    <div class="row">
        {{-- Phase 1: Export --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-download"></i> {{ __('messages.factory.inventory.phase1_title') }}
                </div>
                <div class="card-body">
                    <p>{{ __('messages.factory.inventory.phase1_description') }}</p>
                    <ul class="text-muted small">
                        <li>{{ __('messages.factory.inventory.column_id') }}</li>
                        <li>{{ __('messages.factory.inventory.column_name') }}</li>
                        <li>{{ __('messages.factory.inventory.column_unit') }}</li>
                        <li>{{ __('messages.factory.inventory.column_theoretical') }}</li>
                        <li><strong>{{ __('messages.factory.inventory.column_counted') }}</strong></li>
                    </ul>
                    <p class="text-muted small">{{ __('messages.factory.inventory.phase1_instructions') }}</p>
                </div>
                <div class="card-footer">
                    <a href="{{ route('factory.inventory.export') }}" class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-spreadsheet"></i> {{ __('messages.factory.inventory.download_template') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Phase 2: Import --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-upload"></i> {{ __('messages.factory.inventory.phase2_title') }}
                </div>
                <div class="card-body">
                    <p>{{ __('messages.factory.inventory.phase2_description') }}</p>

                    <form action="{{ route('factory.inventory.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="inventory_file" class="form-label">{{ __('messages.factory.inventory.select_file') }}</label>
                            <input type="file" class="form-control @error('inventory_file') is-invalid @enderror" id="inventory_file" name="inventory_file" accept=".csv" required>
                            @error('inventory_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('messages.factory.inventory.file_format_hint') }}</div>
                        </div>

                        <div class="alert alert-warning small">
                            <i class="bi bi-exclamation-triangle"></i> {{ __('messages.factory.inventory.warning_message') }}
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> {{ __('messages.factory.inventory.import_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Success message with adjustments --}}
    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>

        @if(session('adjustments') && count(session('adjustments')) > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-list-check"></i> {{ __('messages.factory.inventory.adjustments_made') }}
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.factory.material') }}</th>
                                <th class="text-end">{{ __('messages.factory.inventory.previous_stock') }}</th>
                                <th class="text-end">{{ __('messages.factory.inventory.new_stock') }}</th>
                                <th class="text-end">{{ __('messages.factory.inventory.difference') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('adjustments') as $adj)
                                <tr>
                                    <td>{{ $adj['name'] }}</td>
                                    <td class="text-end">{{ number_format($adj['from'], 2) }}</td>
                                    <td class="text-end">{{ number_format($adj['to'], 2) }}</td>
                                    <td class="text-end {{ $adj['difference'] > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $adj['difference'] > 0 ? '+' : '' }}{{ number_format($adj['difference'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Import errors --}}
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle"></i> {{ __('messages.factory.inventory.import_warnings') }}</h6>
            <ul class="mb-0">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Debug info (temporary) --}}
    @if(session('debug_info') && config('app.debug'))
        <div class="alert alert-info">
            <h6><i class="bi bi-bug"></i> Debug Info (visible en mode debug uniquement)</h6>
            <pre class="mb-0 small">{{ json_encode(session('debug_info'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('factory.raw-materials.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>
@endsection
