@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.popup_event.create_title') }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('popup-events.store') }}" method="POST" id="event-form">
        @csrf

        <div class="row mb-4">
            <div class="col-md-3">
                <label for="name" class="form-label fw-bold">{{ __('messages.popup_event.name') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-3">
                <label for="location" class="form-label fw-bold">{{ __('messages.popup_event.location') }}</label>
                <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" placeholder="{{ __('messages.popup_event.location_placeholder') }}">
            </div>
            <div class="col-md-3">
                <label for="store_id" class="form-label fw-bold">{{ __('messages.popup_event.store') }}</label>
                <select name="store_id" id="store_id" class="form-select" required>
                    <option value="">{{ __('messages.popup_event.select_store') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label fw-bold">{{ __('messages.popup_event.start_date') }}</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <label for="end_date" class="form-label fw-bold">{{ __('messages.popup_event.end_date') }}</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}">
            </div>
            <div class="col-md-9">
                <label for="notes" class="form-label">{{ __('messages.popup_event.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" value="{{ old('notes') }}" placeholder="{{ __('messages.popup_event.notes_placeholder') }}">
            </div>
        </div>

        {{-- Product Search --}}
        <div id="search-section" class="d-none">
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
        <div id="products-section" class="d-none">
            <table class="table table-striped" id="products-table">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th>{{ __('messages.popup_event.available_stock') }}</th>
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
            <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                <i class="bi bi-check-circle"></i> {{ __('messages.popup_event.create_event') }}
            </button>
            <a href="{{ route('popup-events.index') }}" class="btn btn-secondary">
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
    const searchSection = document.getElementById('search-section');
    const productsSection = document.getElementById('products-section');
    const searchInput = document.getElementById('product-search');
    const searchResults = document.getElementById('search-results');
    const productsTbody = document.getElementById('products-tbody');
    const submitBtn = document.getElementById('submit-btn');

    let searchTimeout = null;
    let addedProducts = {};
    let itemIndex = 0;

    storeSelect.addEventListener('change', function() {
        if (this.value) {
            searchSection.classList.remove('d-none');
            productsTbody.innerHTML = '';
            addedProducts = {};
            itemIndex = 0;
            updateSummary();
        } else {
            searchSection.classList.add('d-none');
            productsSection.classList.add('d-none');
        }
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
                                <span class="badge bg-secondary">$${product.price}</span>
                                ${isAdded ? '<span class="badge bg-success ms-1">&#10003;</span>' : ''}
                            </div>
                        `;

                        if (!isAdded) {
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                addProduct(product);
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

    function addProduct(product) {
        if (addedProducts[product.id]) return;

        addedProducts[product.id] = true;
        const idx = itemIndex++;

        const row = document.createElement('tr');
        row.dataset.productId = product.id;
        row.innerHTML = `
            <td>${product.ean || '-'}</td>
            <td>${product.name}</td>
            <td>${product.brand || '-'}</td>
            <td><span class="badge bg-info">${product.stock}</span></td>
            <td>
                <input type="number" class="form-control form-control-sm quantity-input"
                       min="1" max="${product.stock}" value="1" style="max-width:100px;" required>
                <input type="hidden" name="products[${idx}][product_id]" value="${product.id}">
                <input type="hidden" name="products[${idx}][quantity_allocated]" value="1" class="quantity-hidden">
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
        productsSection.classList.remove('d-none');
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

        if (rows.length === 0) {
            productsSection.classList.add('d-none');
        }
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
