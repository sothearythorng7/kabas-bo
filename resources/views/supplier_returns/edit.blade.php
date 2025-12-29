@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_return.edit_title') }} #{{ $return->id }} - {{ $supplier->name }}</h1>

    <div class="alert alert-info mb-3">
        <i class="bi bi-info-circle"></i> {{ __('messages.supplier_return.edit_info') }}
    </div>

    <form action="{{ route('supplier-returns.update', [$supplier, $return]) }}" method="POST" id="return-form">
        @csrf
        @method('PUT')

        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">{{ __('messages.supplier.store_name') }}</label>
                <input type="text" class="form-control" value="{{ $return->store->name }}" disabled>
            </div>
            <div class="col-md-8">
                <label for="notes" class="form-label">{{ __('messages.supplier_return.notes') }}</label>
                <input type="text" name="notes" id="notes" class="form-control" value="{{ $return->notes }}" placeholder="{{ __('messages.supplier_return.notes_placeholder') }}">
            </div>
        </div>

        <div id="products-section">
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
                    @foreach($products as $index => $product)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input product-checkbox" data-index="{{ $index }}" {{ $product['return_qty'] > 0 ? 'checked' : '' }}>
                            </td>
                            <td>{{ $product['ean'] ?? '-' }}</td>
                            <td>{{ $product['name'] }}</td>
                            <td>{{ $product['brand'] ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $product['stock'] }}</span></td>
                            <td>
                                <input type="number"
                                       class="form-control form-control-sm quantity-input"
                                       data-index="{{ $index }}"
                                       min="0"
                                       max="{{ $product['stock'] }}"
                                       value="{{ $product['return_qty'] }}"
                                       style="max-width: 100px;"
                                       {{ $product['return_qty'] > 0 ? '' : 'disabled' }}>
                                <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product['id'] }}" {{ $product['return_qty'] > 0 ? '' : 'disabled' }}>
                                <input type="hidden" name="products[{{ $index }}][quantity]" value="{{ $product['return_qty'] }}" class="quantity-hidden" {{ $product['return_qty'] > 0 ? '' : 'disabled' }}>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="alert alert-secondary" id="summary-section">
                <strong>{{ __('messages.supplier_return.summary') }}:</strong>
                <span id="selected-count">0</span> {{ __('messages.supplier_return.products_selected') }},
                <span id="total-quantity">0</span> {{ __('messages.supplier_return.units_total') }}
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success" id="submit-btn">
                <i class="bi bi-check-circle"></i> {{ __('messages.btn.update') }}
            </button>
            <a href="{{ route('supplier-returns.show', [$supplier, $return]) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submit-btn');
    const selectedCountEl = document.getElementById('selected-count');
    const totalQuantityEl = document.getElementById('total-quantity');

    // Add event listeners for checkboxes
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const index = this.dataset.index;
            const row = this.closest('tr');
            const quantityInput = row.querySelector('.quantity-input');
            const hiddenInputs = row.querySelectorAll('input[type="hidden"]');

            if (this.checked) {
                quantityInput.disabled = false;
                hiddenInputs.forEach(input => input.disabled = false);
                if (parseInt(quantityInput.value) === 0) {
                    quantityInput.value = 1;
                    row.querySelector('.quantity-hidden').value = 1;
                }
            } else {
                quantityInput.disabled = true;
                quantityInput.value = 0;
                hiddenInputs.forEach(input => {
                    if (input.classList.contains('quantity-hidden')) {
                        input.value = 0;
                    }
                    input.disabled = true;
                });
            }

            updateSummary();
        });
    });

    // Add event listeners for quantity inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const hiddenQuantity = this.closest('tr').querySelector('.quantity-hidden');
            hiddenQuantity.value = this.value;
            updateSummary();
        });
    });

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

    // Initial summary update
    updateSummary();
});
</script>
@endpush
