@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Edit value for variation")</h1>
    <form action="{{ route('variation-values.update', $variationValue) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">@t("warehouse_invoices.type")</label>
            <select name="variation_type_id" class="form-select @error('variation_type_id') is-invalid @enderror" required>
                <option value="">@t("Select type")</option>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ old('variation_type_id', $variationValue->variation_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                @endforeach
            </select>
            @error('variation_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">@t("variation.value")</label>
            <input type="text" name="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $variationValue->value) }}" required>
            @error('value') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-success">@t("save")</button>
        <a href="{{ route('variation-values.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
