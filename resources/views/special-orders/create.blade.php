@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">{{ __('messages.special_order.create_title') }}</h1>
        <a href="{{ route('special-orders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.special_order.back_to_list') }}
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('special-orders.store') }}" method="POST" id="specialOrderForm">
        @csrf

        <div class="row">
            <!-- Left column: Client + Items -->
            <div class="col-lg-8">
                <!-- Client info -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-person"></i> {{ __('messages.special_order.client_info') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.first_name') }} *</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.last_name') }} *</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.email') }} <span id="emailRequired" style="display:none">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" id="emailInput">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.phone') }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store & Payment options -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-gear"></i> {{ __('messages.special_order.order_options') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.store') }} *</label>
                                <select name="store_id" class="form-select" id="storeSelect" required>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ old('store_id', 3) == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('messages.special_order.store_help') }}</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.payment_type_label') }} *</label>
                                <select name="payment_type" class="form-select" id="paymentTypeSelect" required>
                                    <option value="payment_link" {{ old('payment_type', 'payment_link') === 'payment_link' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_payment_link') }}
                                    </option>
                                    <option value="cash" {{ old('payment_type') === 'cash' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_cash') }}
                                    </option>
                                    <option value="bank_transfer" {{ old('payment_type') === 'bank_transfer' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_bank_transfer') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="row" id="depositSection">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.deposit_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="deposit_amount" class="form-control" step="0.01" min="0" value="{{ old('deposit_amount', 0) }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="deposit_paid" value="1" id="depositPaid" {{ old('deposit_paid') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="depositPaid">{{ __('messages.special_order.deposit_paid') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping address (optional) -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="hasShipping" name="has_shipping" value="1" {{ old('has_shipping') ? 'checked' : '' }}>
                            <label class="form-check-label" for="hasShipping">
                                <i class="bi bi-geo-alt"></i> {{ __('messages.special_order.add_shipping_address') }}
                            </label>
                        </div>
                    </div>
                    <div class="card-body" id="shippingFields" style="{{ old('has_shipping') ? '' : 'display:none' }}">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.address_line1') }}</label>
                                <input type="text" name="shipping_address_line1" class="form-control" value="{{ old('shipping_address_line1') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.address_line2') }}</label>
                                <input type="text" name="shipping_address_line2" class="form-control" value="{{ old('shipping_address_line2') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.city') }}</label>
                                <input type="text" name="shipping_city" class="form-control" value="{{ old('shipping_city') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.postal_code') }}</label>
                                <input type="text" name="shipping_postal_code" class="form-control" value="{{ old('shipping_postal_code') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.state') }}</label>
                                <input type="text" name="shipping_state" class="form-control" value="{{ old('shipping_state') }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.country') }}</label>
                                <input type="text" name="shipping_country" class="form-control" value="{{ old('shipping_country') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cart3"></i> {{ __('messages.special_order.products') }}</span>
                    </div>
                    <div class="card-body">
                        <!-- Search bar -->
                        <div class="position-relative mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="productSearch" class="form-control" placeholder="{{ __('messages.special_order.search_products') }}" autocomplete="off">
                            </div>
                            <div id="searchResults" class="dropdown-menu w-100 shadow" style="display:none; position:absolute; top:100%; left:0; z-index:1050; max-height:300px; overflow-y:auto;"></div>
                        </div>

                        <!-- Items table -->
                        <div class="table-responsive">
                            <table class="table table-sm" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('messages.special_order.product_name') }}</th>
                                        <th>EAN</th>
                                        <th class="text-center">{{ __('messages.special_order.stock') }}</th>
                                        <th class="text-end">{{ __('messages.special_order.public_price') }}</th>
                                        <th class="text-end" style="width: 130px;">{{ __('messages.special_order.custom_price') }}</th>
                                        <th class="text-center" style="width: 90px;">{{ __('messages.special_order.qty') }}</th>
                                        <th class="text-end">{{ __('messages.special_order.line_total') }}</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="noItemsRow">
                                        <td colspan="8" class="text-center text-muted py-3">
                                            {{ __('messages.special_order.no_items_yet') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Summary -->
            <div class="col-lg-4">
                <!-- Order total -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-calculator"></i> {{ __('messages.special_order.order_total') }}
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('messages.special_order.items_count') }}</span>
                            <strong id="totalItems">0</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fs-5 fw-bold">{{ __('messages.special_order.total') }}</span>
                            <span class="fs-5 fw-bold" id="totalAmount">$0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Admin notes -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-pencil-square"></i> {{ __('messages.special_order.admin_notes') }}
                    </div>
                    <div class="card-body">
                        <textarea name="admin_notes" class="form-control" rows="4" placeholder="{{ __('messages.special_order.admin_notes_placeholder') }}">{{ old('admin_notes') }}</textarea>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                        <i class="bi bi-check-circle"></i> <span id="submitBtnText">{{ __('messages.special_order.create_and_generate_link') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const searchResults = document.getElementById('searchResults');
    const itemsBody = document.getElementById('itemsBody');
    const totalItems = document.getElementById('totalItems');
    const totalAmount = document.getElementById('totalAmount');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const hasShipping = document.getElementById('hasShipping');
    const shippingFields = document.getElementById('shippingFields');
    const storeSelect = document.getElementById('storeSelect');
    const paymentTypeSelect = document.getElementById('paymentTypeSelect');
    const emailInput = document.getElementById('emailInput');
    const emailRequired = document.getElementById('emailRequired');

    let items = [];
    let itemIndex = 0;
    let searchTimeout = null;

    // Toggle shipping address
    hasShipping.addEventListener('change', function() {
        shippingFields.style.display = this.checked ? '' : 'none';
    });

    // Payment type change: update button text + email requirement
    function updatePaymentType() {
        const type = paymentTypeSelect.value;
        if (type === 'payment_link') {
            submitBtnText.textContent = '{{ __("messages.special_order.create_and_generate_link") }}';
            emailRequired.style.display = '';
            emailInput.required = true;
        } else if (type === 'cash') {
            submitBtnText.textContent = '{{ __("messages.special_order.create_paid_cash") }}';
            emailRequired.style.display = 'none';
            emailInput.required = false;
        } else {
            submitBtnText.textContent = '{{ __("messages.special_order.create_paid_transfer") }}';
            emailRequired.style.display = 'none';
            emailInput.required = false;
        }
    }
    paymentTypeSelect.addEventListener('change', updatePaymentType);
    updatePaymentType();

    // Product search — pass store_id for stock
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();

        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            const storeId = storeSelect.value;
            fetch(`{{ route('special-orders.search-products') }}?q=${encodeURIComponent(q)}&store_id=${storeId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(products => {
                searchResults.innerHTML = '';

                if (products.length === 0) {
                    searchResults.innerHTML = '<div class="dropdown-item text-muted">{{ __("messages.special_order.no_products_found") }}</div>';
                    searchResults.style.display = 'block';
                    return;
                }

                products.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'dropdown-item cursor-pointer';
                    div.style.cursor = 'pointer';
                    div.innerHTML = `<strong>${escapeHtml(p.name)}</strong> <small class="text-muted">${escapeHtml(p.ean || '')}</small>` +
                        `<span class="float-end"><span class="badge bg-${p.stock > 0 ? 'success' : 'danger'}">${p.stock}</span> $${p.price.toFixed(2)}</span>`;
                    div.addEventListener('click', () => addItem(p));
                    searchResults.appendChild(div);
                });

                searchResults.style.display = 'block';
            });
        }, 300);
    });

    // Close search results on outside click
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // Re-fetch stock when store changes
    storeSelect.addEventListener('change', function() {
        // Clear stock display in current items (will refresh on next search)
        items.forEach(item => { item.stock = '?'; });
        renderItems();
    });

    function addItem(product) {
        const existing = items.find(i => i.product_id === product.id);
        if (existing) {
            existing.quantity++;
            renderItems();
            searchInput.value = '';
            searchResults.style.display = 'none';
            return;
        }

        items.push({
            index: itemIndex++,
            product_id: product.id,
            name: product.name,
            ean: product.ean,
            stock: product.stock,
            public_price: product.price,
            custom_price: product.price,
            quantity: 1,
        });

        renderItems();
        searchInput.value = '';
        searchResults.style.display = 'none';
    }

    function removeItem(index) {
        items = items.filter(i => i.index !== index);
        renderItems();
    }

    function renderItems() {
        itemsBody.innerHTML = '';

        if (items.length === 0) {
            itemsBody.innerHTML = `<tr id="noItemsRow"><td colspan="8" class="text-center text-muted py-3">{{ __('messages.special_order.no_items_yet') }}</td></tr>`;
            submitBtn.disabled = true;
            totalItems.textContent = '0';
            totalAmount.textContent = '$0.00';
            return;
        }

        let total = 0;
        let count = 0;

        items.forEach((item, idx) => {
            const lineTotal = item.custom_price * item.quantity;
            total += lineTotal;
            count += item.quantity;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    ${escapeHtml(item.name)}
                    <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                </td>
                <td><code>${escapeHtml(item.ean || '-')}</code></td>
                <td class="text-center"><span class="badge bg-${item.stock > 0 ? 'success' : (item.stock === '?' ? 'secondary' : 'danger')}">${item.stock}</span></td>
                <td class="text-end text-muted">$${item.public_price.toFixed(2)}</td>
                <td class="text-end">
                    <input type="number" name="items[${idx}][custom_price]" class="form-control form-control-sm text-end"
                           value="${item.custom_price.toFixed(2)}" step="0.01" min="0" data-index="${item.index}" data-field="custom_price">
                </td>
                <td class="text-center">
                    <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center"
                           value="${item.quantity}" min="1" data-index="${item.index}" data-field="quantity">
                </td>
                <td class="text-end fw-bold">$${lineTotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${item.index}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            itemsBody.appendChild(tr);
        });

        totalItems.textContent = count;
        totalAmount.textContent = '$' + total.toFixed(2);
        submitBtn.disabled = false;

        // Bind events
        itemsBody.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', () => removeItem(parseInt(btn.dataset.remove)));
        });

        itemsBody.querySelectorAll('input[data-field]').forEach(input => {
            input.addEventListener('change', function() {
                const item = items.find(i => i.index === parseInt(this.dataset.index));
                if (!item) return;

                if (this.dataset.field === 'custom_price') {
                    item.custom_price = parseFloat(this.value) || 0;
                } else if (this.dataset.field === 'quantity') {
                    item.quantity = parseInt(this.value) || 1;
                }
                renderItems();
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>
@endpush
