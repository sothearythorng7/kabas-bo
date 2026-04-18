@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.stock_loss.create_title') }}</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-losses.store') }}" method="POST" id="loss-form">
        @csrf

        <div class="row mb-4">
            <div class="col-md-3">
                <label for="store_id" class="form-label fw-bold">{{ __('messages.stock_loss.store') }}</label>
                <select name="store_id" id="store_id" class="form-select" required>
                    <option value="">{{ __('messages.stock_loss.select_store') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label fw-bold">{{ __('messages.stock_loss.type') }}</label>
                <select name="type" id="type" class="form-select" required>
                    <option value="">{{ __('messages.stock_loss.select_type') }}</option>
                    <option value="pure_loss">{{ __('messages.stock_loss.type_pure_loss') }}</option>
                    <option value="supplier_refund">{{ __('messages.stock_loss.type_supplier_refund') }}</option>
                </select>
            </div>
            <div class="col-md-3" id="supplier-field" style="display:none;">
                <label for="supplier_id" class="form-label fw-bold">{{ __('messages.stock_loss.supplier') }}</label>
                <select name="supplier_id" id="supplier_id" class="form-select">
                    <option value="">{{ __('messages.stock_loss.select_supplier') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="reason" class="form-label fw-bold">{{ __('messages.stock_loss.reason') }}</label>
                <input type="text" name="reason" id="reason" class="form-control" placeholder="e.g. Damaged, Expired, Broken...">
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <label for="notes" class="form-label">{{ __('messages.stock_loss.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" placeholder="{{ __('messages.stock_loss.notes_placeholder') }}">
            </div>
        </div>

        {{-- Search --}}
        <div id="search-section" class="d-none">
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">{{ __('messages.stock_loss.search_product') }}</label>
                    <div class="position-relative">
                        <input type="text" id="product-search" class="form-control"
                               placeholder="{{ __('messages.stock_loss.search_placeholder') }}" autocomplete="off">
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
                        <th>{{ __('messages.supplier.stock') }}</th>
                        <th>{{ __('messages.stock_loss.quantity') }}</th>
                        <th>{{ __('messages.stock_loss.unit_cost') }}</th>
                        <th>{{ __('messages.stock_loss.loss_reason') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                </tbody>
            </table>

            <div class="alert alert-secondary" id="summary-section">
                <strong>{{ __('messages.stock_loss.summary') }}:</strong>
                <span id="selected-count">0</span> {{ __('messages.stock_loss.products_selected') }},
                <span id="total-quantity">0</span> {{ __('messages.stock_loss.units_total') }},
                {{ __('messages.stock_loss.total_value') }}: $<span id="total-value">0.00</span>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                <i class="bi bi-check-circle"></i> {{ __('messages.stock_loss.create_loss') }}
            </button>
            <a href="{{ route('stock-losses.index') }}" class="btn btn-secondary">
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
    const typeSelect = document.getElementById('type');
    const supplierField = document.getElementById('supplier-field');
    const supplierSelect = document.getElementById('supplier_id');
    const searchSection = document.getElementById('search-section');
    const productsSection = document.getElementById('products-section');
    const searchInput = document.getElementById('product-search');
    const searchResults = document.getElementById('search-results');
    const productsTbody = document.getElementById('products-tbody');
    const submitBtn = document.getElementById('submit-btn');

    let searchTimeout = null;
    let addedProducts = {};
    let itemIndex = 0;

    // Show/hide supplier field
    typeSelect.addEventListener('change', function() {
        supplierField.style.display = this.value === 'supplier_refund' ? '' : 'none';
        supplierSelect.required = this.value === 'supplier_refund';
        if (this.value !== 'supplier_refund') supplierSelect.value = '';
    });

    // Show search when store is selected
    storeSelect.addEventListener('change', function() {
        if (this.value) {
            searchSection.classList.remove('d-none');
            // Clear existing products when store changes
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

    // Meilisearch
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

            fetch(`{{ url('stock-losses/search') }}/${storeId}?q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    searchResults.innerHTML = '';

                    if (data.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item text-muted">{{ __("messages.stock_loss.no_products_in_stock") }}</div>';
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
                                <span class="badge bg-secondary">$${product.avg_cost}</span>
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

    // Close search results on click outside
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
                       min="1" max="${product.stock}" value="1" style="max-width:80px;" required>
                <input type="hidden" name="products[${idx}][product_id]" value="${product.id}">
                <input type="hidden" name="products[${idx}][quantity]" value="1" class="quantity-hidden">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm cost-input"
                       min="0" step="0.00001" value="${product.avg_cost}" style="max-width:100px;" required>
                <input type="hidden" name="products[${idx}][unit_cost]" value="${product.avg_cost}" class="cost-hidden">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm reason-input" style="max-width:150px;">
                <input type="hidden" name="products[${idx}][loss_reason]" value="" class="reason-hidden">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger remove-btn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        `;

        // Quantity sync
        row.querySelector('.quantity-input').addEventListener('input', function() {
            row.querySelector('.quantity-hidden').value = this.value;
            updateSummary();
        });

        // Cost sync
        row.querySelector('.cost-input').addEventListener('input', function() {
            row.querySelector('.cost-hidden').value = this.value;
            updateSummary();
        });

        // Reason sync
        row.querySelector('.reason-input').addEventListener('input', function() {
            row.querySelector('.reason-hidden').value = this.value;
        });

        // Remove
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
        let totalVal = 0;

        rows.forEach(row => {
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
            totalQty += qty;
            totalVal += qty * cost;
        });

        document.getElementById('selected-count').textContent = rows.length;
        document.getElementById('total-quantity').textContent = totalQty;
        document.getElementById('total-value').textContent = totalVal.toFixed(2);
        submitBtn.disabled = rows.length === 0;

        if (rows.length === 0) {
            productsSection.classList.add('d-none');
        }
    }

    // Form validation
    document.getElementById('loss-form').addEventListener('submit', function(e) {
        if (productsTbody.querySelectorAll('tr').length === 0) {
            e.preventDefault();
            alert('Please select at least one product.');
        }
    });
});
</script>
@endpush
