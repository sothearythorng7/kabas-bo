@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_return.create_title') }} - {{ $supplier->name }}</h1>

    <form action="{{ route('supplier-returns.store', $supplier) }}" method="POST" id="return-form">
        @csrf

        <div class="row mb-4">
            <div class="col-md-4">
                <label for="store_id" class="form-label">{{ __('messages.supplier.store_name') }}</label>
                <select name="store_id" id="store_id" class="form-select" required>
                    <option value="">{{ __('messages.supplier_return.select_store') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>
                @error('store_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-8">
                <label for="notes" class="form-label">{{ __('messages.supplier_return.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" placeholder="{{ __('messages.supplier_return.notes_placeholder') }}">
            </div>
        </div>

        <div id="products-section" class="d-none">
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i> {{ __('messages.supplier_return.select_products_info') }}
            </div>

            <table class="table table-striped" id="products-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th>{{ __('messages.supplier.stock') }}</th>
                        <th>{{ __('messages.supplier_return.quantity_to_return') }}</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                </tbody>
            </table>

            <div class="alert alert-secondary" id="summary-section">
                <strong>{{ __('messages.supplier_return.summary') }}:</strong>
                <span id="selected-count">0</span> {{ __('messages.supplier_return.products_selected') }},
                <span id="total-quantity">0</span> {{ __('messages.supplier_return.units_total') }}
            </div>
        </div>

        <div id="no-products-message" class="alert alert-warning d-none">
            <i class="bi bi-exclamation-triangle"></i> {{ __('messages.supplier_return.no_products_in_stock') }}
        </div>

        <div id="loading-message" class="d-none">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            {{ __('messages.common.loading') }}
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                <i class="bi bi-check-circle"></i> {{ __('messages.supplier_return.create_return') }}
            </button>
            <a href="{{ route('suppliers.edit', ['supplier' => $supplier, '#returns']) }}" class="btn btn-secondary">
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
    const productsSection = document.getElementById('products-section');
    const noProductsMessage = document.getElementById('no-products-message');
    const loadingMessage = document.getElementById('loading-message');
    const productsTbody = document.getElementById('products-tbody');
    const submitBtn = document.getElementById('submit-btn');
    const selectedCountEl = document.getElementById('selected-count');
    const totalQuantityEl = document.getElementById('total-quantity');

    let products = [];

    storeSelect.addEventListener('change', function() {
        const storeId = this.value;

        if (!storeId) {
            productsSection.classList.add('d-none');
            noProductsMessage.classList.add('d-none');
            submitBtn.disabled = true;
            return;
        }

        loadingMessage.classList.remove('d-none');
        productsSection.classList.add('d-none');
        noProductsMessage.classList.add('d-none');

        fetch(`{{ url('suppliers/' . $supplier->id . '/returns/products') }}/${storeId}`)
            .then(response => response.json())
            .then(data => {
                loadingMessage.classList.add('d-none');
                products = data;

                if (data.length === 0) {
                    noProductsMessage.classList.remove('d-none');
                    submitBtn.disabled = true;
                    return;
                }

                renderProducts(data);
                productsSection.classList.remove('d-none');
            })
            .catch(error => {
                loadingMessage.classList.add('d-none');
                console.error('Error:', error);
                alert('{{ __("messages.common.error_loading") }}');
            });
    });

    function renderProducts(products) {
        productsTbody.innerHTML = '';

        products.forEach((product, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input product-checkbox" data-index="${index}">
                </td>
                <td>${product.ean || '-'}</td>
                <td>${product.name}</td>
                <td>${product.brand || '-'}</td>
                <td><span class="badge bg-info">${product.stock}</span></td>
                <td>
                    <input type="number"
                           class="form-control form-control-sm quantity-input"
                           data-index="${index}"
                           min="1"
                           max="${product.stock}"
                           value="1"
                           style="max-width: 100px;"
                           disabled>
                    <input type="hidden" name="products[${index}][product_id]" value="${product.id}" disabled>
                    <input type="hidden" name="products[${index}][quantity]" value="1" disabled class="quantity-hidden">
                </td>
            `;
            productsTbody.appendChild(row);
        });

        // Add event listeners
        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const index = this.dataset.index;
                const row = this.closest('tr');
                const quantityInput = row.querySelector('.quantity-input');
                const hiddenInputs = row.querySelectorAll('input[type="hidden"]');

                if (this.checked) {
                    quantityInput.disabled = false;
                    hiddenInputs.forEach(input => input.disabled = false);
                } else {
                    quantityInput.disabled = true;
                    hiddenInputs.forEach(input => input.disabled = true);
                }

                updateSummary();
            });
        });

        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const index = this.dataset.index;
                const hiddenQuantity = this.closest('tr').querySelector('.quantity-hidden');
                hiddenQuantity.value = this.value;
                updateSummary();
            });
        });
    }

    function updateSummary() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        let totalQty = 0;

        checkedBoxes.forEach(checkbox => {
            const row = checkbox.closest('tr');
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            totalQty += qty;
        });

        selectedCountEl.textContent = checkedBoxes.length;
        totalQuantityEl.textContent = totalQty;
        submitBtn.disabled = checkedBoxes.length === 0;
    }

    // Form validation
    document.getElementById('return-form').addEventListener('submit', function(e) {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('{{ __("messages.supplier_return.select_at_least_one") }}');
        }
    });
});
</script>
@endpush
