@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-truck"></i> {{ __('messages.factory.new_supplier') }}</h1>

    <form action="{{ route('factory.suppliers.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">{{ __('messages.common.name') }} *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label">{{ __('messages.common.email') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="phone" class="form-label">{{ __('messages.common.phone') }}</label>
                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('messages.common.active') }}</label>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">{{ __('messages.common.address') }}</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">{{ __('messages.common.notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('factory.suppliers.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
