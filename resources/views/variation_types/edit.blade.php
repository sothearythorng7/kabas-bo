@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.variation.edit_type') }}</h1>
    <form action="{{ route('variation-types.update', $variationType) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">{{ __('messages.variation.name') }}</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $variationType->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.variation.label') }}</label>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $variationType->label) }}" required>
            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('variation-types.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
