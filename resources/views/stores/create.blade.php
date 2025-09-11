@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.store.btnCreate') }}</h1>
    <form action="{{ route('stores.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">{{ __('messages.store.name') }}</label>
            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="address" class="form-label">{{ __('messages.store.address') }}</label>
            <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">{{ __('messages.store.phone') }}</label>
            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" required>
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('messages.store.email') }}</label>
            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="opening_time" class="form-label">{{ __('messages.store.opening_time') }}</label>
            <input type="time" name="opening_time" id="opening_time" class="form-control" value="{{ old('opening_time') }}">
        </div>

        <div class="mb-3">
            <label for="closing_time" class="form-label">{{ __('messages.store.closing_time') }}</label>
            <input type="time" name="closing_time" id="closing_time" class="form-control" value="{{ old('closing_time') }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="is_reseller" id="is_reseller" class="form-check-input" value="1" {{ old('is_reseller') ? 'checked' : '' }}>
            <label for="is_reseller" class="form-check-label">Ce magasin est un revendeur (consignation)</label>
        </div>


        <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('stores.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
