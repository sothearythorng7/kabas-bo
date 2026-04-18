@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">{{ __('messages.special_order.edit_title') }} {{ $order->order_number }}</h1>
        <a href="{{ route('special-orders.show', $order) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.special_order.back_to_detail') }}
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

    <form action="{{ route('special-orders.update', $order) }}" method="POST" id="specialOrderForm">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Left column -->
            <div class="col-lg-8">
                @if($order->status === 'pending')
                <!-- Client info (editable when pending) -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-person"></i> {{ __('messages.special_order.client_info') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.first_name') }} *</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $order->shipping_first_name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.last_name') }} *</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $order->shipping_last_name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.email') }}</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $order->guest_email) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.phone') }}</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $order->guest_phone) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping address (editable when pending) -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-geo-alt"></i> {{ __('messages.special_order.add_shipping_address') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.address_line1') }}</label>
                                <input type="text" name="shipping_address_line1" class="form-control" value="{{ old('shipping_address_line1', $order->shipping_address_line1) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.address_line2') }}</label>
                                <input type="text" name="shipping_address_line2" class="form-control" value="{{ old('shipping_address_line2', $order->shipping_address_line2) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.city') }}</label>
                                <input type="text" name="shipping_city" class="form-control" value="{{ old('shipping_city', $order->shipping_city) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.postal_code') }}</label>
                                <input type="text" name="shipping_postal_code" class="form-control" value="{{ old('shipping_postal_code', $order->shipping_postal_code) }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('messages.special_order.state') }}</label>
                                <input type="text" name="shipping_state" class="form-control" value="{{ old('shipping_state', $order->shipping_state) }}">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ __('messages.special_order.country') }}</label>
                                <input type="text" name="shipping_country" class="form-control" value="{{ old('shipping_country', $order->shipping_country) }}">
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Status & Tracking -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-arrow-repeat"></i> {{ __('messages.website_order.update_status') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.website_order.status') }}</label>
                                <select name="status" class="form-select">
                                    @foreach(\App\Models\WebsiteOrder::statuses() as $status)
                                        <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                            {{ __('messages.website_order.status_' . $status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-truck"></i> {{ __('messages.website_order.tracking_url') }}</label>
                                <input type="url" name="tracking_url" class="form-control" value="{{ $order->tracking_url }}"
                                       placeholder="{{ __('messages.website_order.tracking_url_placeholder') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deposit -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-piggy-bank"></i> {{ __('messages.special_order.deposit') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.deposit_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="deposit_amount" class="form-control" step="0.00001" min="0"
                                           value="{{ old('deposit_amount', $order->deposit_amount) }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="deposit_paid" value="1" id="depositPaid"
                                           {{ old('deposit_paid', $order->deposit_paid) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="depositPaid">{{ __('messages.special_order.deposit_paid') }}</label>
                                </div>
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

                <!-- Paid options -->
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-plus-circle"></i> {{ __('messages.special_order.paid_options') }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">
                            <i class="bi bi-plus"></i> {{ __('messages.special_order.add_option') }}
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="optionsContainer"></div>
                        <div id="noOptionsMsg" class="text-center text-muted py-2">
                            {{ __('messages.special_order.no_options_yet') }}
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-pencil-square"></i> {{ __('messages.special_order.admin_notes') }}
                    </div>
                    <div class="card-body">
                        <textarea name="admin_notes" class="form-control" rows="4">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right column: Summary -->
            <div class="col-lg-4">
                <!-- Order info -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-info-circle"></i> {{ __('messages.website_order.summary') }}
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>{{ __('messages.website_order.client') }}:</strong> {{ $order->shipping_full_name }}</p>
                        <p class="mb-1"><strong>{{ __('messages.special_order.store') }}:</strong> {{ $order->store?->name ?? '-' }}</p>
                        <p class="mb-1"><strong>{{ __('messages.website_order.payment') }}:</strong>
                            <span class="badge bg-{{ \App\Models\WebsiteOrder::paymentStatusBadgeClass($order->payment_status) }}">
                                {{ __('messages.website_order.pay_status_' . $order->payment_status) }}
                            </span>
                        </p>
                        @if($order->payment_status !== 'paid')
                            <div class="mt-2">
                                <label class="form-label fw-bold mb-1">{{ __('messages.special_order.payment_type_label') }}</label>
                                <select name="payment_type" class="form-select form-select-sm">
                                    <option value="payment_link" {{ $order->payment_type === 'payment_link' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_payment_link') }}
                                    </option>
                                    <option value="cash" {{ $order->payment_type === 'cash' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_cash') }}
                                    </option>
                                    <option value="bank_transfer" {{ $order->payment_type === 'bank_transfer' ? 'selected' : '' }}>
                                        {{ __('messages.special_order.type_bank_transfer') }}
                                    </option>
                                </select>
                            </div>
                        @else
                            <p class="mb-0"><strong>{{ __('messages.special_order.payment_type_label') }}:</strong>
                                {{ __('messages.special_order.type_' . $order->payment_type) }}
                            </p>
                        @endif
                        @if($order->status === 'pending')
                            <div class="mt-2">
                                <label class="form-label fw-bold mb-1">{{ __('messages.special_order.store') }}</label>
                                <select name="store_id" class="form-select form-select-sm" id="storeSelectEdit">
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ $order->store_id == $store->id ? 'selected' : '' }}>
                                            {{ $store->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

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
                        <div class="d-flex justify-content-between mb-2" id="optionsTotalRow" style="display:none;">
                            <span>{{ __('messages.special_order.paid_options') }}</span>
                            <strong id="optionsTotal">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ __('messages.special_order.subtotal') }}</span>
                            <strong id="subtotalAmount">$0.00</strong>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">{{ __('messages.special_order.discount') }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">-</span>
                                <input type="number" class="form-control" id="discountInput" step="0.00001" min="0" value="{{ $order->discount ?? 0 }}">
                                <button class="btn btn-outline-secondary" type="button" id="discountToggle" style="min-width:40px;">$</button>
                            </div>
                            <input type="hidden" name="discount_amount" id="discountAmountHidden" value="{{ $order->discount ?? 0 }}">
                            <small class="text-muted" id="discountInfo" style="display:none;"></small>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fs-5 fw-bold">{{ __('messages.special_order.total') }}</span>
                            <span class="fs-5 fw-bold" id="totalAmount">$0.00</span>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="bi bi-save"></i> {{ __('messages.special_order.save_changes') }}
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
    const discountInput = document.getElementById('discountInput');
    const discountAmountHidden = document.getElementById('discountAmountHidden');
    const discountToggle = document.getElementById('discountToggle');
    const discountInfo = document.getElementById('discountInfo');
    const optionsTotalRow = document.getElementById('optionsTotalRow');
    const optionsTotalEl = document.getElementById('optionsTotal');
    const subtotalAmount = document.getElementById('subtotalAmount');
    const optionsContainer = document.getElementById('optionsContainer');
    const noOptionsMsg = document.getElementById('noOptionsMsg');
    const addOptionBtn = document.getElementById('addOptionBtn');

    let items = [];
    let itemIndex = 0;
    let options = [];
    let optionIndex = 0;
    let searchTimeout = null;
    let discountMode = 'amount';
    let storeId = {{ $order->store_id ?? $warehouseId }};

    // Update storeId when store select changes (pending orders)
    const storeSelectEdit = document.getElementById('storeSelectEdit');
    if (storeSelectEdit) {
        storeSelectEdit.addEventListener('change', function() {
            storeId = parseInt(this.value);
        });
    }

    // Load existing product items
    @foreach($order->items->where('item_type', 'product') as $item)
    items.push({
        index: itemIndex++,
        product_id: {{ $item->product_id }},
        name: @json($item->product_name),
        ean: @json($item->product_sku ?? ''),
        stock: '?',
        public_price: {{ (float) $item->unit_price }},
        custom_price: {{ (float) $item->unit_price }},
        quantity: {{ $item->quantity }},
    });
    @endforeach

    // Load existing option items
    @foreach($order->items->where('item_type', 'option') as $item)
    options.push({
        index: optionIndex++,
        label: @json($item->product_name),
        amount: {{ (float) $item->unit_price }},
    });
    @endforeach

    // Toggle discount mode
    discountToggle.addEventListener('click', function() {
        if (discountMode === 'amount') {
            discountMode = 'percent';
            this.textContent = '%';
            discountInput.max = 100;
        } else {
            discountMode = 'amount';
            this.textContent = '$';
            discountInput.removeAttribute('max');
        }
        discountInput.value = 0;
        recalcTotals();
    });

    // Discount input
    discountInput.addEventListener('input', recalcTotals);

    // Product search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();

        if (q.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
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

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
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

    // --- Options ---
    addOptionBtn.addEventListener('click', function() {
        options.push({ index: optionIndex++, label: '', amount: 0 });
        renderOptions();
    });

    function renderOptions() {
        optionsContainer.innerHTML = '';
        noOptionsMsg.style.display = options.length === 0 ? '' : 'none';

        options.forEach((opt, idx) => {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 align-items-center';
            row.innerHTML = `
                <div class="col">
                    <input type="text" name="options[${idx}][label]" class="form-control form-control-sm"
                           placeholder="{{ __('messages.special_order.option_label_placeholder') }}" value="${escapeHtml(opt.label)}" data-opt-index="${opt.index}" data-opt-field="label">
                </div>
                <div class="col-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" name="options[${idx}][amount]" class="form-control text-end"
                               step="0.00001" min="0" value="${opt.amount.toFixed(2)}" data-opt-index="${opt.index}" data-opt-field="amount">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-opt="${opt.index}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            optionsContainer.appendChild(row);
        });

        optionsContainer.querySelectorAll('[data-opt-field]').forEach(input => {
            input.addEventListener('change', function() {
                const opt = options.find(o => o.index === parseInt(this.dataset.optIndex));
                if (!opt) return;
                if (this.dataset.optField === 'label') {
                    opt.label = this.value;
                } else {
                    opt.amount = parseFloat(this.value.replace(',', '.')) || 0;
                }
                recalcTotals();
            });
        });

        optionsContainer.querySelectorAll('[data-remove-opt]').forEach(btn => {
            btn.addEventListener('click', function() {
                options = options.filter(o => o.index !== parseInt(this.dataset.removeOpt));
                renderOptions();
                recalcTotals();
            });
        });
    }

    function recalcTotals() {
        let itemsTotal = 0;
        let count = 0;
        items.forEach(item => {
            itemsTotal += item.custom_price * item.quantity;
            count += item.quantity;
        });

        let optTotal = 0;
        options.forEach(opt => { optTotal += opt.amount; });

        const subtotal = itemsTotal + optTotal;
        const inputVal = parseFloat(discountInput.value) || 0;
        let discountAmount;

        if (discountMode === 'percent') {
            discountAmount = Math.round(subtotal * inputVal / 100 * 100000) / 100000;
            discountInfo.textContent = '-$' + discountAmount.toFixed(2) + ' (' + inputVal + '%)';
            discountInfo.style.display = '';
        } else {
            discountAmount = inputVal;
            discountInfo.style.display = 'none';
        }

        discountAmountHidden.value = discountAmount.toFixed(5);
        const total = Math.max(0, subtotal - discountAmount);

        totalItems.textContent = count;
        subtotalAmount.textContent = '$' + subtotal.toFixed(2);
        totalAmount.textContent = '$' + total.toFixed(2);

        if (optTotal > 0) {
            optionsTotalRow.style.display = '';
            optionsTotalRow.classList.remove('d-none');
            optionsTotalEl.textContent = '$' + optTotal.toFixed(2);
        } else {
            optionsTotalRow.style.display = 'none';
        }

        submitBtn.disabled = items.length === 0;
    }

    function renderItems() {
        itemsBody.innerHTML = '';

        if (items.length === 0) {
            itemsBody.innerHTML = `<tr id="noItemsRow"><td colspan="8" class="text-center text-muted py-3">{{ __('messages.special_order.no_items_yet') }}</td></tr>`;
            recalcTotals();
            return;
        }

        items.forEach((item, idx) => {
            const lineTotal = item.custom_price * item.quantity;

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
                           value="${item.custom_price.toFixed(2)}" step="0.00001" min="0" data-index="${item.index}" data-field="custom_price">
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

        recalcTotals();

        itemsBody.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', () => removeItem(parseInt(btn.dataset.remove)));
        });

        itemsBody.querySelectorAll('input[data-field]').forEach(input => {
            input.addEventListener('change', function() {
                const item = items.find(i => i.index === parseInt(this.dataset.index));
                if (!item) return;

                if (this.dataset.field === 'custom_price') {
                    item.custom_price = parseFloat(this.value.replace(',', '.')) || 0;
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

    // Initial render
    renderItems();
    renderOptions();
});
</script>
@endpush
