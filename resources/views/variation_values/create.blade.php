@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.variation_value.title_create') }}</h1>
    <form action="{{ route('variation-values.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ __('messages.variation.type') }}</label>
            <select name="variation_type_id" class="form-select @error('variation_type_id') is-invalid @enderror" required>
                <option value="">{{ __('messages.variation_value.select_type') }}</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ old('variation_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            @error('variation_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.variation.value') }}</label>
            <input type="text" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value') }}" required>
            @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('messages.variation_value.color_hex') }}</label>
            <div class="d-flex align-items-center gap-2">
                <input type="color" id="color_picker" value="{{ old('color_hex', '#000000') }}" style="width: 50px; height: 38px; padding: 2px;">
                <input type="text" name="color_hex" id="color_hex" class="form-control @error('color_hex') is-invalid @enderror" value="{{ old('color_hex') }}" placeholder="#RRGGBB" pattern="^#[0-9A-Fa-f]{6}$" style="max-width: 140px;">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear_color">{{ __('messages.variation_value.color_hex_clear') }}</button>
                <small class="text-muted">{{ __('messages.variation_value.color_hex_hint') }}</small>
            </div>
            @error('color_hex') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('variation-values.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>

<script>
(function() {
    const picker = document.getElementById('color_picker');
    const text = document.getElementById('color_hex');
    const clear = document.getElementById('clear_color');
    if (text.value && /^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    picker.addEventListener('input', e => { text.value = e.target.value.toUpperCase(); });
    text.addEventListener('input', e => {
        if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) picker.value = e.target.value;
    });
    clear.addEventListener('click', () => { text.value = ''; });
})();
</script>
@endsection
