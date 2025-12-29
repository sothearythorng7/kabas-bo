@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-box-seam"></i> {{ __('messages.btn.edit') }}: {{ $rawMaterial->name }}</h1>

    <form action="{{ route('factory.raw-materials.update', $rawMaterial) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">{{ __('messages.common.name') }} *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $rawMaterial->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="sku" class="form-label">{{ __('messages.factory.sku') }}</label>
                <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $rawMaterial->sku) }}">
                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="unit" class="form-label">{{ __('messages.factory.unit') }} *</label>
                <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                    @foreach($units as $key => $label)
                        <option value="{{ $key }}" {{ old('unit', $rawMaterial->unit) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="supplier_id" class="form-label">{{ __('messages.factory.supplier') }}</label>
                <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                    <option value="">-- {{ __('messages.common.select') }} --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $rawMaterial->supplier_id) == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="alert_quantity" class="form-label">{{ __('messages.factory.alert_quantity') }}</label>
                <input type="number" step="0.01" min="0" class="form-control @error('alert_quantity') is-invalid @enderror" id="alert_quantity" name="alert_quantity" value="{{ old('alert_quantity', $rawMaterial->alert_quantity) }}">
                @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">{{ __('messages.common.description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description', $rawMaterial->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="track_stock" name="track_stock" value="1" {{ old('track_stock', $rawMaterial->track_stock) ? 'checked' : '' }}>
                    <label class="form-check-label" for="track_stock">
                        {{ __('messages.factory.track_stock') }}
                        <small class="text-muted d-block">{{ __('messages.factory.track_stock_help') }}</small>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $rawMaterial->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('messages.common.active') }}</label>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
            <a href="{{ route('factory.raw-materials.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
            <button type="submit" form="clone-form" class="btn btn-primary">
                <i class="bi bi-copy"></i> {{ __('messages.factory.clone_material') }}
            </button>
        </div>
    </form>

    <form id="clone-form" action="{{ route('factory.raw-materials.clone', $rawMaterial) }}" method="POST" class="d-none">
        @csrf
    </form>

    {{-- Section Stock (si gestion de stock activée) --}}
    @if($rawMaterial->track_stock)
    <hr class="my-4">

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-boxes"></i> {{ __('messages.factory.stock') }}</span>
                    <span class="badge bg-{{ $rawMaterial->isLowStock() ? 'danger' : 'success' }} fs-6">
                        {{ number_format($rawMaterial->total_stock, 2) }} {{ $rawMaterial->unit }}
                    </span>
                </div>
                <div class="card-body">
                    {{-- Formulaire ajout stock --}}
                    <form action="{{ route('factory.raw-materials.add-stock', $rawMaterial) }}" method="POST">
                        @csrf
                        <h6>{{ __('messages.factory.add_stock') }}</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="number" step="0.01" min="0.01" name="quantity" class="form-control form-control-sm" placeholder="{{ __('messages.factory.quantity') }}" required>
                            </div>
                            <div class="col-md-4">
                                <input type="number" step="0.01" min="0" name="unit_price" class="form-control form-control-sm" placeholder="{{ __('messages.factory.unit_price') }}">
                            </div>
                            <div class="col-md-4">
                                <input type="date" name="received_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="batch_number" class="form-control form-control-sm" placeholder="{{ __('messages.factory.batch_number') }}">
                            </div>
                            <div class="col-md-6">
                                <input type="date" name="expires_at" class="form-control form-control-sm" placeholder="{{ __('messages.factory.expires_at') }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="bi bi-plus-circle"></i> {{ __('messages.factory.add_stock') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-archive"></i> {{ __('messages.factory.stock_batches') }}
                </div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    @if($rawMaterial->stockBatches->isEmpty())
                        <p class="text-muted mb-0">{{ __('messages.factory.no_stock') }}</p>
                    @else
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.factory.quantity') }}</th>
                                    <th>{{ __('messages.factory.batch_number') }}</th>
                                    <th>{{ __('messages.common.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rawMaterial->stockBatches as $batch)
                                    <tr class="{{ $batch->isExpired() ? 'table-danger' : ($batch->isExpiringSoon() ? 'table-warning' : '') }}">
                                        <td>{{ number_format($batch->quantity, 2) }} {{ $rawMaterial->unit }}</td>
                                        <td>{{ $batch->batch_number ?? '-' }}</td>
                                        <td>
                                            {{ $batch->received_at?->format('d/m/Y') ?? '-' }}
                                            @if($batch->expires_at)
                                                <br><small class="text-muted">Exp: {{ $batch->expires_at->format('d/m/Y') }}</small>
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
    @endif
</div>
@endsection
