@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-gear"></i> {{ __('messages.factory.new_production') }}</h1>

    <form action="{{ route('factory.productions.store') }}" method="POST" id="production-form">
        @csrf

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="recipe_id" class="form-label">{{ __('messages.factory.recipe') }} *</label>
                <select class="form-select @error('recipe_id') is-invalid @enderror" id="recipe_id" name="recipe_id" required>
                    <option value="">-- {{ __('messages.common.select') }} --</option>
                    @foreach($recipes as $recipe)
                        <option value="{{ $recipe->id }}"
                            data-product="{{ $recipe->product->name[app()->getLocale()] ?? $recipe->product->name['en'] ?? '-' }}"
                            data-items='@json($recipe->items)'
                            data-max="{{ $recipe->maxProducible() }}"
                            {{ ($selectedRecipe?->id ?? old('recipe_id')) == $recipe->id ? 'selected' : '' }}>
                            {{ $recipe->name }}
                        </option>
                    @endforeach
                </select>
                @error('recipe_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="quantity_produced" class="form-label">{{ __('messages.factory.quantity_to_produce') }} *</label>
                <input type="number" min="1" class="form-control @error('quantity_produced') is-invalid @enderror" id="quantity_produced" name="quantity_produced" value="{{ old('quantity_produced', 1) }}" required>
                @error('quantity_produced') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-muted" id="max-info"></small>
            </div>
            <div class="col-md-4 mb-3">
                <label for="produced_at" class="form-label">{{ __('messages.common.date') }} *</label>
                <input type="date" class="form-control @error('produced_at') is-invalid @enderror" id="produced_at" name="produced_at" value="{{ old('produced_at', now()->format('Y-m-d')) }}" required>
                @error('produced_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="batch_number" class="form-label">{{ __('messages.factory.batch_number') }}</label>
                <input type="text" class="form-control @error('batch_number') is-invalid @enderror" id="batch_number" name="batch_number" value="{{ old('batch_number') }}" placeholder="{{ __('messages.factory.auto_generated') }}">
                @error('batch_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('messages.factory.product') }}</label>
                <input type="text" class="form-control" id="product-display" readonly disabled>
            </div>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">{{ __('messages.common.notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Consommations --}}
        <hr>
        <h4><i class="bi bi-box-arrow-down"></i> {{ __('messages.factory.materials_consumed') }}</h4>
        <p class="text-muted">{{ __('messages.factory.materials_consumed_help') }}</p>

        <div id="consumptions-container">
            {{-- Généré dynamiquement --}}
        </div>

        <div class="alert alert-info" id="no-recipe-alert">
            <i class="bi bi-info-circle"></i> {{ __('messages.factory.select_recipe_first') }}
        </div>

        <hr>

        @if(!$warehouse)
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> {{ __('messages.factory.no_warehouse') }}
            </div>
        @else
            <div class="alert alert-success">
                <i class="bi bi-building"></i> {{ __('messages.factory.stock_destination') }}: <strong>{{ $warehouse->name }}</strong>
            </div>
            <button class="btn btn-success btn-lg"><i class="bi bi-check-circle"></i> {{ __('messages.factory.create_production') }}</button>
        @endif

        <a href="{{ route('factory.productions.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const recipeSelect = document.getElementById('recipe_id');
    const quantityInput = document.getElementById('quantity_produced');
    const productDisplay = document.getElementById('product-display');
    const maxInfo = document.getElementById('max-info');
    const consumptionsContainer = document.getElementById('consumptions-container');
    const noRecipeAlert = document.getElementById('no-recipe-alert');

    function updateConsumptions() {
        const option = recipeSelect.options[recipeSelect.selectedIndex];

        if (!option || !option.value) {
            productDisplay.value = '';
            maxInfo.textContent = '';
            consumptionsContainer.innerHTML = '';
            noRecipeAlert.style.display = 'block';
            return;
        }

        noRecipeAlert.style.display = 'none';
        productDisplay.value = option.dataset.product;

        const max = parseInt(option.dataset.max) || 0;
        maxInfo.textContent = '{{ __('messages.factory.max_producible') }}: ' + max;

        const items = JSON.parse(option.dataset.items || '[]');
        const qty = parseInt(quantityInput.value) || 1;

        consumptionsContainer.innerHTML = '';

        items.forEach((item, index) => {
            const material = item.raw_material;
            const suggestedQty = (item.quantity * qty).toFixed(4);

            const row = document.createElement('div');
            row.className = 'row mb-2 align-items-center';
            row.innerHTML = `
                <input type="hidden" name="consumptions[${index}][raw_material_id]" value="${item.raw_material_id}">
                <div class="col-md-4">
                    <strong>${material.name}</strong>
                    ${material.track_stock ? '' : '<span class="badge bg-info ms-1">{{ __('messages.factory.not_tracked_short') }}</span>'}
                    ${item.is_optional ? '<span class="badge bg-secondary ms-1">{{ __('messages.factory.optional') }}</span>' : ''}
                </div>
                <div class="col-md-2 text-muted">
                    ${item.quantity} ${material.unit} x ${qty}
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.0001" min="0" name="consumptions[${index}][quantity_consumed]"
                            class="form-control" value="${suggestedQty}"
                            ${material.track_stock ? '' : 'readonly'}>
                        <span class="input-group-text">${material.unit}</span>
                    </div>
                </div>
                <div class="col-md-3 text-muted small">
                    ${material.track_stock ? '{{ __('messages.factory.stock') }}: ' + (material.total_stock || 0).toFixed(2) + ' ' + material.unit : '{{ __('messages.factory.not_managed') }}'}
                </div>
            `;
            consumptionsContainer.appendChild(row);
        });
    }

    recipeSelect.addEventListener('change', updateConsumptions);
    quantityInput.addEventListener('input', updateConsumptions);

    // Initialiser si une recette est pré-sélectionnée
    updateConsumptions();
});
</script>
@endpush
@endsection
