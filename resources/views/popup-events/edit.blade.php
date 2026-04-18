@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.popup_event.edit_title') }} {{ $popupEvent->reference }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('popup-events.update', $popupEvent) }}" method="POST" id="event-form">
        @csrf
        @method('PUT')

        <div class="row mb-4">
            <div class="col-md-3">
                <label for="name" class="form-label fw-bold">{{ __('messages.popup_event.name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $popupEvent->name) }}" required>
            </div>
            <div class="col-md-3">
                <label for="location" class="form-label fw-bold">{{ __('messages.popup_event.location') }}</label>
                <input type="text" name="location" id="location" class="form-control" value="{{ old('location', $popupEvent->location) }}">
            </div>
            <div class="col-md-3">
                <label for="store_id" class="form-label fw-bold">{{ __('messages.popup_event.store') }}</label>
                <select name="store_id" id="store_id" class="form-select" required>
                    <option value="">{{ __('messages.popup_event.select_store') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id', $popupEvent->store_id) == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label fw-bold">{{ __('messages.popup_event.start_date') }}</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', $popupEvent->start_date->format('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <label for="end_date" class="form-label fw-bold">{{ __('messages.popup_event.end_date') }}</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', $popupEvent->end_date?->format('Y-m-d')) }}">
            </div>
            <div class="col-md-9">
                <label for="notes" class="form-label">{{ __('messages.popup_event.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" value="{{ old('notes', $popupEvent->notes) }}">
            </div>
        </div>

        {{-- Product Search --}}
        <div id="search-section">
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">{{ __('messages.popup_event.search_product') }}</label>
                    <div class="position-relative">
                        <input type="text" id="product-search" class="form-control"
                               placeholder="{{ __('messages.popup_event.search_placeholder') }}" autocomplete="off">
                        <div id="search-results" class="list-group position-absolute w-100 shadow" style="z-index:1050; max-height:300px; overflow-y:auto; display:none;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Selected Products Table --}}
        <div id="products-section">
            <table class="table table-striped" id="products-table">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th>{{ __('messages.popup_event.quantity_allocated') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                </tbody>
            </table>

            <div class="alert alert-secondary" id="summary-section">
                <strong>{{ __('messages.popup_event.summary') }}:</strong>
                <span id="selected-count">0</span> {{ __('messages.popup_event.products_selected') }},
                <span id="total-quantity">0</span> {{ __('messages.popup_event.units_total') }}
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success" id="submit-btn">
                <i class="bi bi-check-circle"></i> {{ __('messages.btn.save') }}
            </button>
            <a href="{{ route('popup-events.show', $popupEvent) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const storeSelect = document.getElementById('store_id');
    const searchInput = document.getElementById('product-search');
    const searchResults = document.getElementById('search-results');
    const productsTbody = document.getElementById('products-tbody');
    const submitBtn = document.getElementById('submit-btn');

    let searchTimeout = null;
    let addedProducts = {};
    let itemIndex = 0;

    // Load existing items
    const existingItems = @json($popupEvent->items->map(fn($item) => [
        'id' => $item->product_id,
        'name' => is_array($item->product->name) ? ($item->product->name[app()->getLocale()] ?? reset($item->product->name)) : $item->product->name,
        'ean' => $item->product->ean,
        'brand' => $item->product->brand?->name,
        'quantity' => $item->quantity_allocated,
    ]));

    existingItems.forEach(item => addProduct(item, item.quantity));

    storeSelect.addEventListener('change', function() {
        productsTbody.innerHTML = '';
        addedProducts = {};
        itemIndex = 0;
        updateSummary();
        searchInput.value = '';
        searchResults.style.display = 'none';
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 1) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            const storeId = storeSelect.value;
            if (!storeId) return;

            fetch(`{{ url('popup-events/search') }}/${storeId}?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    searchResults.innerHTML = '';

                    if (data.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item text-muted">{{ __("messages.popup_event.no_products_found") }}</div>';
                        searchResults.style.display = 'block';
                        return;
                    }

                    data.forEach(product => {
                        const isAdded = addedProducts[product.id];
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center' + (isAdded ? ' disabled bg-light' : '');
                        item.innerHTML = `
                            <div>
                                <strong>${product.name}</strong>
                                <small class="text-muted ms-2">${product.ean || ''}</small>
                                ${product.brand ? '<small class="text-muted ms-2">(' + product.brand + ')</small>' : ''}
                            </div>
                            <div>
                                <span class="badge bg-info me-2">Stock: ${product.stock}</span>
                                ${isAdded ? '<span class="badge bg-success ms-1">&#10003;</span>' : ''}
                            </div>
                        `;

                        if (!isAdded) {
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                addProduct(product, 1);
                                searchInput.value = '';
                                searchResults.style.display = 'none';
                                searchInput.focus();
                            });
                        }

                        searchResults.appendChild(item);
                    });

                    searchResults.style.display = 'block';
                })
                .catch(err => console.error('Search error:', err));
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    function addProduct(product, qty) {
        if (addedProducts[product.id]) return;

        addedProducts[product.id] = true;
        const idx = itemIndex++;

        const row = document.createElement('tr');
        row.dataset.productId = product.id;
        row.innerHTML = `
            <td>${product.ean || '-'}</td>
            <td>${product.name}</td>
            <td>${product.brand || '-'}</td>
            <td>
                <input type="number" class="form-control form-control-sm quantity-input"
                       min="1" value="${qty || 1}" style="max-width:100px;" required>
                <input type="hidden" name="products[${idx}][product_id]" value="${product.id}">
                <input type="hidden" name="products[${idx}][quantity_allocated]" value="${qty || 1}" class="quantity-hidden">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-btn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        row.querySelector('.quantity-input').addEventListener('input', function() {
            row.querySelector('.quantity-hidden').value = this.value;
            updateSummary();
        });

        row.querySelector('.remove-btn').addEventListener('click', function() {
            delete addedProducts[product.id];
            row.remove();
            updateSummary();
        });

        productsTbody.appendChild(row);
        updateSummary();
    }

    function updateSummary() {
        const rows = productsTbody.querySelectorAll('tr');
        let totalQty = 0;

        rows.forEach(row => {
            totalQty += parseInt(row.querySelector('.quantity-input').value) || 0;
        });

        document.getElementById('selected-count').textContent = rows.length;
        document.getElementById('total-quantity').textContent = totalQty;
        submitBtn.disabled = rows.length === 0;
    }

    document.getElementById('event-form').addEventListener('submit', function(e) {
        if (productsTbody.querySelectorAll('tr').length === 0) {
            e.preventDefault();
            alert('{{ __("messages.popup_event.select_at_least_one") }}');
        }
    });
});
</script>
@endpush
