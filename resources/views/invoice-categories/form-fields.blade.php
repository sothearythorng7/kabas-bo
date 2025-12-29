<div class="mb-3">
    <label>{{ __('messages.invoice_category.name') }} *</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label>{{ __('messages.invoice_category.color') }} *</label>
    <input type="color" name="color" class="form-control" value="{{ old('color', $category->color ?? '#6c757d') }}" required>
    <small class="form-text text-muted">{{ __('messages.invoice_category.color_hint') }}</small>
</div>
