@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-box-seam"></i> {{ __('messages.factory.new_material') }}</h1>

    <form action="{{ route('factory.raw-materials.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">{{ __('messages.common.name') }} *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="sku" class="form-label">{{ __('messages.factory.sku') }}</label>
                <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku') }}">
                @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="unit" class="form-label">{{ __('messages.factory.unit') }} *</label>
                <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                    @foreach($units as $key => $label)
                        <option value="{{ $key }}" {{ old('unit', 'unit') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="supplier_id" class="form-label">{{ __('messages.factory.supplier') }}</label>
                <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                    <option value="">-- {{ __('messages.common.select') }} --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="alert_quantity" class="form-label">{{ __('messages.factory.alert_quantity') }}</label>
                <input type="number" step="0.01" min="0" class="form-control @error('alert_quantity') is-invalid @enderror" id="alert_quantity" name="alert_quantity" value="{{ old('alert_quantity') }}">
                @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">{{ __('messages.common.description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="track_stock" name="track_stock" value="1" {{ old('track_stock', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="track_stock">
                        {{ __('messages.factory.track_stock') }}
                        <small class="text-muted d-block">{{ __('messages.factory.track_stock_help') }}</small>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('messages.common.active') }}</label>
                </div>
            </div>
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('factory.raw-materials.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
