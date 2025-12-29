@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-journal-plus"></i> {{ __('messages.factory.new_recipe') }}</h1>

    <form action="{{ route('factory.recipes.store') }}" method="POST" id="recipe-form">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">{{ __('messages.factory.recipe_name') }} *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="product_id" class="form-label">{{ __('messages.factory.product') }} *</label>
                <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                    <option value="">-- {{ __('messages.common.search') }} --</option>
                </select>
                @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-muted">{{ __('messages.factory.product_search_help') }}</small>
            </div>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">{{ __('messages.common.description') }}</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="instructions" class="form-label">{{ __('messages.factory.instructions') }}</label>
            <textarea class="form-control @error('instructions') is-invalid @enderror" id="instructions" name="instructions" rows="3">{{ old('instructions') }}</textarea>
            @error('instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">{{ __('messages.common.active') }}</label>
        </div>

        {{-- Ingrédients --}}
        <hr>
        <h4><i class="bi bi-list-check"></i> {{ __('messages.factory.ingredients') }}</h4>

        <div id="items-container">
            {{-- Items dynamiques --}}
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-item">
            <i class="bi bi-plus-circle"></i> {{ __('messages.factory.add_ingredient') }}
        </button>

        <hr>
        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('factory.recipes.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const materials = @json($materials);
    let itemIndex = 0;

    // Recherche produit
    const productSelect = document.getElementById('product_id');
    let searchTimeout;

    // Convertir en select searchable (simple implementation)
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'form-control';
    searchInput.placeholder = '{{ __('messages.factory.product_search_placeholder') }}';

    const searchResults = document.createElement('div');
    searchResults.className = 'list-group position-absolute w-100';
    searchResults.style.zIndex = '1000';
    searchResults.style.maxHeight = '200px';
    searchResults.style.overflowY = 'auto';
    searchResults.style.display = 'none';

    const wrapper = document.createElement('div');
    wrapper.className = 'position-relative';
    productSelect.parentNode.insertBefore(wrapper, productSelect);
    wrapper.appendChild(searchInput);
    wrapper.appendChild(searchResults);
    productSelect.style.display = 'none';

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value;

        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch('{{ route('products.search') }}?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(products => {
                    searchResults.innerHTML = '';
                    products.forEach(p => {
                        const name = p.name['{{ app()->getLocale() }}'] || p.name['en'] || p.ean;
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = name + ' (' + p.ean + ')';
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            productSelect.innerHTML = '<option value="' + p.id + '" selected>' + name + '</option>';
                            searchInput.value = name;
                            searchResults.style.display = 'none';
                        });
                        searchResults.appendChild(item);
                    });
                    searchResults.style.display = products.length ? 'block' : 'none';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Gestion des ingrédients
    function addItem(data = {}) {
        const container = document.getElementById('items-container');
        const row = document.createElement('div');
        row.className = 'row mb-2 item-row';
        row.innerHTML = `
            <div class="col-md-5">
                <select name="items[${itemIndex}][raw_material_id]" class="form-select form-select-sm" required>
                    <option value="">-- {{ __('messages.factory.select_material') }} --</option>
                    ${materials.map(m => `<option value="${m.id}" ${data.raw_material_id == m.id ? 'selected' : ''}>${m.name} (${m.unit})${m.track_stock ? '' : ' - {{ __('messages.factory.not_tracked_short') }}'}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.0001" min="0.0001" name="items[${itemIndex}][quantity]" class="form-control form-control-sm" placeholder="{{ __('messages.factory.quantity_per_unit') }}" value="${data.quantity || ''}" required>
            </div>
            <div class="col-md-2">
                <div class="form-check mt-1">
                    <input type="checkbox" class="form-check-input" name="items[${itemIndex}][is_optional]" value="1" ${data.is_optional ? 'checked' : ''}>
                    <label class="form-check-label small">{{ __('messages.factory.optional') }}</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(row);
        itemIndex++;
    }

    document.getElementById('add-item').addEventListener('click', () => addItem());

    document.getElementById('items-container').addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.item-row').remove();
        }
    });

    // Ajouter au moins un item par défaut
    addItem();
});
</script>
@endpush
@endsection
