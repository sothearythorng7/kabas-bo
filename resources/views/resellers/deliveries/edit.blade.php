@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.deliveries') }} #{{ $delivery->id }} - {{ __('messages.btn.edit') }}</h1>
    @php
        $resellerType = $delivery->getResellerType();
        $productsCount = $delivery->products->count();
        if($resellerType == 'buyer')
        {
            $totalPaid = $delivery->invoice?->payments->sum('amount') ?? 0;
            $remaining = max(($delivery->invoice?->total_amount ?? 0) - $totalPaid, 0);
            $paymentsCount = $delivery->invoice?->payments->count() ?? 0;

            // Statut de paiement
            if(!$delivery->invoice) {
                $paymentStatus = 'N/A';
            } elseif($remaining <= 0) {
                $paymentStatus = 'paid';
            } elseif($totalPaid > 0) {
                $paymentStatus = 'partially_paid';
            } else {
                $paymentStatus = 'unpaid';
            }
        }

    @endphp

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="deliveryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.resellers.tab_general') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }} <span class="badge bg-secondary">{{ $productsCount }}</span>
            </button>
        </li>
        @if($resellerType == 'buyer')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                {{ __('messages.resellers.payments') }} <span class="badge bg-secondary">{{ $paymentsCount }}</span>
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="invoice-tab" data-bs-toggle="tab" data-bs-target="#invoice" type="button" role="tab" aria-controls="invoice" aria-selected="false">
                <i class="bi bi-receipt"></i> {{ __('messages.resellers.invoice') }}
                @if($delivery->invoice)
                    <span class="badge bg-success">{{ __('messages.resellers.created') }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="deliveryTabsContent">
        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">

                {{-- Infos paiement --}}
                @if($delivery->invoice && $resellerType == 'buyer')
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('messages.resellers.total_to_pay') }}</h6>
                                <p class="card-text">${{ number_format($delivery->invoice->total_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-info">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('messages.resellers.total_already_paid') }}</h6>
                                <p class="card-text">${{ number_format($totalPaid, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-warning">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('messages.resellers.remaining_to_pay') }}</h6>
                                <p class="card-text">${{ number_format($remaining, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-secondary text-white">
                            <div class="card-body">
                                <h6 class="card-title">{{ __('messages.resellers.payment_status') }}</h6>
                                <p class="card-text">
                                    @if($paymentStatus === 'paid')
                                        <span class="badge bg-success">{{ __('messages.resellers.paid') }}</span>
                                    @elseif($paymentStatus === 'partially_paid')
                                        <span class="badge bg-warning text-dark">{{ __('messages.resellers.partially_paid') }}</span>
                                    @elseif($paymentStatus === 'unpaid')
                                        <span class="badge bg-danger">{{ __('messages.resellers.unpaid') }}</span>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            <form action="{{ route('reseller-stock-deliveries.update', [$reseller->id, $delivery->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="status" class="form-label">{{ __('messages.resellers.status') }}</label>
                    <select name="status" id="status" class="form-select">
                        @foreach(\App\Models\ResellerStockDelivery::STATUS_OPTIONS as $key => $label)
                            <option value="{{ $key }}" @selected($delivery->status === $key)>{{ __('messages.order.' . strtolower($key)) ?? $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="shipping_cost" class="form-label">{{ __('messages.resellers.shipping_cost') }}</label>
                    <input type="number" step="0.01" name="shipping_cost" id="shipping_cost" class="form-control" value="{{ old('shipping_cost', $delivery->shipping_cost) }}">
                    <small class="text-muted">{{ __('messages.resellers.edit_only_after_creation') }}</small>
                </div>

                <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">
                    {{ __('messages.btn.cancel') }}
                </a>
            </form>

            {{-- Delivery Note Section --}}
            <hr class="my-4">
            <h5><i class="bi bi-file-earmark-text"></i> {{ __('messages.resellers.delivery_note') }}</h5>

            @if($delivery->delivery_note)
                <div class="alert alert-success d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bi bi-check-circle me-2"></i>
                        {{ __('messages.resellers.delivery_note_uploaded_status') }}
                    </div>
                    <div>
                        <a href="{{ asset('storage/' . $delivery->delivery_note) }}" target="_blank" class="btn btn-sm btn-primary me-2">
                            <i class="bi bi-eye"></i> {{ __('messages.btn.view') }}
                        </a>
                        <a href="{{ asset('storage/' . $delivery->delivery_note) }}" download class="btn btn-sm btn-outline-primary me-2">
                            <i class="bi bi-download"></i> {{ __('messages.btn.download') }}
                        </a>
                        <form action="{{ route('reseller-stock-deliveries.delete-note', [$reseller->id, $delivery->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.resellers.confirm_delete_note') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ __('messages.resellers.no_delivery_note') }}
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <form action="{{ route('reseller-stock-deliveries.upload-note', [$reseller->id, $delivery->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="delivery_note" class="form-label">{{ __('messages.resellers.upload_delivery_note') }}</label>
                            <input type="file" name="delivery_note" id="delivery_note" class="form-control @error('delivery_note') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png">
                            @error('delivery_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">{{ __('messages.resellers.delivery_note_formats') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> {{ __('messages.btn.upload') }}
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.resellers.generate_delivery_note') }}</label>
                        <p class="text-muted small">{{ __('messages.resellers.generate_delivery_note_desc') }}</p>
                        <a href="{{ route('reseller-stock-deliveries.generate-note', [$reseller->id, $delivery->id]) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.resellers.generate_pdf') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            @if($delivery->status === 'draft')
            {{-- EDITABLE MODE --}}
            <form action="{{ route('reseller-stock-deliveries.update-products', [$reseller->id, $delivery->id]) }}" method="POST" id="updateProductsForm">
                @csrf

                <h4 class="mt-3">{{ __('messages.resellers.current_products') }}</h4>

                <!-- Desktop current products -->
                <div class="d-none d-md-block">
                    <table class="table table-striped mt-2" id="currentProductsTable">
                        <thead>
                            <tr>
                                <th>{{ __('messages.stock_value.ean') }}</th>
                                <th>{{ __('messages.product.name') }}</th>
                                <th>{{ __('messages.product.brand') }}</th>
                                <th>{{ __('messages.resellers.quantity') }}</th>
                                <th>{{ __('messages.product.price_btob') }}</th>
                                <th>{{ __('messages.resellers.line_total') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->products as $product)
                                <tr id="current-row-{{ $product->id }}">
                                    <td>{{ $product->ean }}</td>
                                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                    <td>{{ $product->brand?->name ?? '-' }}</td>
                                    <td>
                                        <input type="number"
                                            name="products[{{ $product->id }}][quantity]"
                                            class="form-control form-control-sm current-qty-input"
                                            data-product-id="{{ $product->id }}"
                                            min="0"
                                            max="{{ $product->pivot->quantity + ($product->available_stock ?? 0) }}"
                                            value="{{ $product->pivot->quantity }}"
                                            style="width: 80px;">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01"
                                            name="products[{{ $product->id }}][unit_price]"
                                            class="form-control form-control-sm current-price-input"
                                            data-product-id="{{ $product->id }}"
                                            value="{{ $product->pivot->unit_price }}"
                                            min="0"
                                            style="width: 100px;">
                                    </td>
                                    <td class="current-line-total" data-product-id="{{ $product->id }}">
                                        ${{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-product-btn" data-product-id="{{ $product->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>{{ __('messages.resellers.total') }}</strong></td>
                                <td id="grandTotalDesktop"><strong>${{ number_format($delivery->products->sum(fn($p) => $p->pivot->quantity * $p->pivot->unit_price), 2) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mobile current products -->
                <div class="d-md-none mt-2">
                    @foreach($delivery->products as $product)
                        <div class="card shadow-sm mb-3" id="current-card-{{ $product->id }}">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="card-title mb-1">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                    <button type="button" class="btn btn-sm btn-danger remove-product-btn" data-product-id="{{ $product->id }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                                <p class="mb-1"><strong>{{ __('messages.product.brand') }}:</strong> {{ $product->brand?->name ?? '-' }}</p>
                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label mb-0">{{ __('messages.resellers.quantity') }}</label>
                                        <input type="number"
                                            name="products[{{ $product->id }}][quantity]"
                                            class="form-control form-control-sm current-qty-input"
                                            data-product-id="{{ $product->id }}"
                                            min="0"
                                            max="{{ $product->pivot->quantity + ($product->available_stock ?? 0) }}"
                                            value="{{ $product->pivot->quantity }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label mb-0">{{ __('messages.product.price_btob') }}</label>
                                        <input type="number" step="0.01"
                                            name="products[{{ $product->id }}][unit_price]"
                                            class="form-control form-control-sm current-price-input"
                                            data-product-id="{{ $product->id }}"
                                            value="{{ $product->pivot->unit_price }}"
                                            min="0">
                                    </div>
                                </div>
                                <p class="mb-0 mt-1"><strong>{{ __('messages.resellers.line_total') }}:</strong> <span class="current-line-total" data-product-id="{{ $product->id }}">${{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</span></p>
                                <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                            </div>
                        </div>
                    @endforeach
                    <div class="text-end mb-3">
                        <strong>{{ __('messages.resellers.total') }}: <span id="grandTotalMobile">${{ number_format($delivery->products->sum(fn($p) => $p->pivot->quantity * $p->pivot->unit_price), 2) }}</span></strong>
                    </div>
                </div>

                {{-- Add new products section --}}
                @if($warehouseProducts->count() > 0)
                <hr>
                <h4>
                    <a class="text-decoration-none" data-bs-toggle="collapse" href="#addProductsSection" role="button" aria-expanded="false" aria-controls="addProductsSection">
                        <i class="bi bi-plus-circle"></i> {{ __('messages.resellers.add_products') }}
                    </a>
                </h4>
                <div class="collapse" id="addProductsSection">
                    <div class="mb-3">
                        <input type="text" id="newProductFilter" class="form-control" placeholder="{{ __('messages.resellers.search_products') }}">
                    </div>

                    <!-- Desktop new products -->
                    <div class="d-none d-md-block">
                        <table class="table table-striped" id="newProductTable">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.stock_value.ean') }}</th>
                                    <th>{{ __('messages.product.name') }}</th>
                                    <th>{{ __('messages.product.brand') }}</th>
                                    <th>{{ __('messages.resellers.quantity') }}</th>
                                    <th>{{ __('messages.product.price_btob') }}</th>
                                    <th>{{ __('messages.resellers.stock_available') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouseProducts as $product)
                                    <tr>
                                        <td>{{ $product->ean }}</td>
                                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td>
                                            <input type="number"
                                                name="products[{{ $product->id }}][quantity]"
                                                class="form-control form-control-sm new-qty-input"
                                                data-product-id="{{ $product->id }}"
                                                min="0"
                                                max="{{ $product->available_stock }}"
                                                value="0"
                                                style="width: 80px;">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01"
                                                name="products[{{ $product->id }}][unit_price]"
                                                class="form-control form-control-sm new-price-input"
                                                data-product-id="{{ $product->id }}"
                                                value="{{ $product->reseller_price }}"
                                                min="0"
                                                style="width: 100px;">
                                        </td>
                                        <td>{{ $product->available_stock }}</td>
                                        <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile new products -->
                    <div class="d-md-none">
                        @foreach($warehouseProducts as $product)
                            <div class="card shadow-sm mb-3 new-product-card">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                    <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.product.brand') }}:</strong> {{ $product->brand?->name ?? '-' }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.stock_available') }}:</strong> {{ $product->available_stock }}</p>
                                    <div class="row g-2 mt-1">
                                        <div class="col-6">
                                            <label class="form-label mb-0">{{ __('messages.resellers.quantity') }}</label>
                                            <input type="number"
                                                name="products[{{ $product->id }}][quantity]"
                                                class="form-control form-control-sm new-qty-input"
                                                data-product-id="{{ $product->id }}"
                                                min="0"
                                                max="{{ $product->available_stock }}"
                                                value="0">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-0">{{ __('messages.product.price_btob') }}</label>
                                            <input type="number" step="0.01"
                                                name="products[{{ $product->id }}][unit_price]"
                                                class="form-control form-control-sm new-price-input"
                                                data-product-id="{{ $product->id }}"
                                                value="{{ $product->reseller_price }}"
                                                min="0">
                                        </div>
                                    </div>
                                    <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> {{ __('messages.resellers.update_products') }}
                    </button>
                </div>
            </form>

            @else
            {{-- READ-ONLY MODE --}}
            <h3>{{ __('messages.product.products') }} {{ __('messages.resellers.in_delivery') }}</h3>

            <!-- Desktop -->
            <div class="d-none d-md-block">
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>{{ __('messages.stock_value.ean') }}</th>
                            <th>{{ __('messages.product.name') }}</th>
                            <th>{{ __('messages.product.brand') }}</th>
                            <th>{{ __('messages.resellers.quantity') }}</th>
                            <th>{{ __('messages.product.price_btob') }}</th>
                            <th>{{ __('messages.resellers.total_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($delivery->products as $product)
                            <tr>
                                <td>{{ $product->ean }}</td>
                                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                <td>{{ $product->pivot->quantity }}</td>
                                <td>{{ number_format($product->pivot->unit_price, 2) }}</td>
                                <td>{{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile -->
            <div class="d-md-none mt-3">
                <div class="row">
                    @foreach($delivery->products as $product)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                    <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.product.brand') }}:</strong> {{ $product->brand?->name ?? '-' }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.quantity') }}:</strong> {{ $product->pivot->quantity }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.product.price_btob') }}:</strong> {{ number_format($product->pivot->unit_price, 2) }}</p>
                                    <p class="mb-0"><strong>{{ __('messages.resellers.total_value') }}:</strong> {{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @if($resellerType == 'buyer')
        {{-- Onglet Paiements --}}
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
            @if($paymentsCount > 0)
                <!-- Desktop -->
                <div class="d-none d-md-block">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('messages.resellers.date') }}</th>
                                <th>{{ __('messages.resellers.amount') }}</th>
                                <th>{{ __('messages.resellers.method') }}</th>
                                <th>{{ __('messages.resellers.reference') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($delivery->invoice?->payments ?? [] as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ ucfirst($payment->payment_method) }}</td>
                                    <td>{{ $payment->reference }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile -->
                <div class="d-md-none">
                    <div class="row">
                        @foreach($delivery->invoice?->payments ?? [] as $payment)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <p class="mb-1"><strong>{{ __('messages.resellers.date') }} :</strong> {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.amount') }} :</strong> {{ number_format($payment->amount, 2) }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.method') }} :</strong> {{ ucfirst($payment->payment_method) }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.reference') }} :</strong> {{ $payment->reference }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    {{ __('messages.resellers.no_payments_delivery') }}
                </div>
            @endif

            <!-- Bouton modal pour ajouter paiement -->
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                {{ __('messages.resellers.add_payment') }}
            </button>
        </div>
        @endif

        {{-- Onglet Invoice --}}
        <div class="tab-pane fade" id="invoice" role="tabpanel" aria-labelledby="invoice-tab">
            <h4><i class="bi bi-receipt"></i> {{ __('messages.resellers.invoice_details') }}</h4>

            {{-- Résumé de la livraison --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('messages.resellers.delivery') }} #{{ $delivery->id }}</strong>
                    <span class="badge bg-{{ $delivery->status === 'shipped' ? 'success' : ($delivery->status === 'draft' ? 'secondary' : 'warning') }} ms-2">
                        {{ __('messages.order.' . strtolower($delivery->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>{{ __('messages.resellers.recipient') }}:</strong><br>
                            {{ $reseller->name }}
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('messages.resellers.date') }}:</strong><br>
                            {{ $delivery->created_at->format('d/m/Y H:i') }}
                        </div>
                        <div class="col-md-4">
                            <strong>{{ __('messages.resellers.status') }}:</strong><br>
                            {{ __('messages.order.' . strtolower($delivery->status)) }}
                        </div>
                    </div>

                    {{-- Tableau des produits --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('messages.product.ean') }}</th>
                                    <th>{{ __('messages.product.name') }}</th>
                                    <th>{{ __('messages.product.brand') }}</th>
                                    <th class="text-end">{{ __('messages.resellers.unit_price') }}</th>
                                    <th class="text-center">{{ __('messages.resellers.quantity') }}</th>
                                    <th class="text-end">{{ __('messages.resellers.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $grandTotal = 0; $totalQty = 0; @endphp
                                @foreach($delivery->products as $product)
                                    @php
                                        $lineTotal = $product->pivot->quantity * $product->pivot->unit_price;
                                        $grandTotal += $lineTotal;
                                        $totalQty += $product->pivot->quantity;
                                    @endphp
                                    <tr>
                                        <td>{{ $product->ean }}</td>
                                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td class="text-end">$ {{ number_format($product->pivot->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $product->pivot->quantity }}</td>
                                        <td class="text-end">$ {{ number_format($lineTotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>{{ __('messages.resellers.total') }}</strong></td>
                                    <td class="text-center"><strong>{{ $totalQty }}</strong></td>
                                    <td class="text-end"><strong>$ {{ number_format($grandTotal, 2) }}</strong></td>
                                </tr>
                                @if($delivery->shipping_cost > 0)
                                <tr>
                                    <td colspan="5" class="text-end">{{ __('messages.resellers.shipping_cost') }}</td>
                                    <td class="text-end">$ {{ number_format($delivery->shipping_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>{{ __('messages.resellers.grand_total') }}</strong></td>
                                    <td class="text-end"><strong>$ {{ number_format($grandTotal + $delivery->shipping_cost, 2) }}</strong></td>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2">
                @if($delivery->invoice)
                    <a href="{{ route('reseller-invoices.show', $delivery->invoice->id) }}" class="btn btn-info">
                        <i class="bi bi-eye"></i> {{ __('messages.resellers.view_invoice') }}
                    </a>
                @else
                    <form action="{{ route('reseller-stock-deliveries.create-invoice', [$reseller->id, $delivery->id]) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> {{ __('messages.resellers.create_invoice') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('reseller-stock-deliveries.generate-invoice-pdf', [$reseller->id, $delivery->id]) }}" class="btn btn-primary">
                    <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.resellers.download_invoice_pdf') }}
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal ajout paiement -->
<!-- Modal ajout paiement -->
 @if($resellerType == 'buyer')
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @if($delivery->invoice)
            <form action="{{ route('reseller-invoices.addPayment', $delivery->invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">{{ __('messages.resellers.add_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <p><strong>{{ __('messages.resellers.total_amount_label') }}:</strong> {{ number_format($delivery->invoice?->total_amount ?? 0, 2) }}</p>
                    <p><strong>{{ __('messages.resellers.already_paid') }} :</strong> {{ number_format($totalPaid, 2) }}</p>
                    <p><strong>{{ __('messages.resellers.remaining') }} :</strong> {{ number_format($remaining, 2) }}</p>
                    <div class="mb-3">
                        <label>{{ __('messages.resellers.amount') }}</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control" max="{{ $remaining }}" required>
                        <div id="amountWarning" class="text-danger mt-1" style="display:none;">
                            {{ __('messages.resellers.amount_cannot_exceed') }} {{ number_format($remaining, 2) }}.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>{{ __('messages.resellers.method') }}</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">{{ __('messages.resellers.cash') }}</option>
                            <option value="transfer">{{ __('messages.resellers.transfer') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>{{ __('messages.resellers.reference') }}</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.resellers.save') }}</button>
                </div>
            </form>
            @else
                <div class="alert alert-warning">
                    {{ __('messages.resellers.no_invoice_for_delivery') }}
                </div>
            @endif
        </div>
    </div>
</div>


@push('scripts')
<script>
document.getElementById('paymentAmount')?.addEventListener('input', function() {
    const max = {{ $remaining }};
    const warning = document.getElementById('amountWarning');
    if (parseFloat(this.value) > max) {
        this.value = max;
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
});
</script>
@endpush
@endif

@if($delivery->status === 'draft')
@push('scripts')
<script>
(function() {
    // Filter for new products
    const filterInput = document.getElementById('newProductFilter');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            // Desktop rows
            document.querySelectorAll('#newProductTable tbody tr').forEach(tr => {
                const ean = tr.cells[0].textContent.toLowerCase();
                const name = tr.cells[1].textContent.toLowerCase();
                tr.style.display = (ean.includes(filter) || name.includes(filter)) ? '' : 'none';
            });
            // Mobile cards
            document.querySelectorAll('.new-product-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // Calculate line totals and grand total
    function recalcTotals() {
        let grandTotal = 0;

        // Current products
        document.querySelectorAll('.current-qty-input').forEach(qtyInput => {
            const productId = qtyInput.dataset.productId;
            // Only count from desktop (avoid double-counting)
            if (!qtyInput.closest('.d-md-none') && !qtyInput.closest('.card')) {
                const qty = parseInt(qtyInput.value) || 0;
                const priceInput = document.querySelector(`.current-price-input[data-product-id="${productId}"]`);
                const price = priceInput ? (parseFloat(priceInput.value) || 0) : 0;
                const lineTotal = qty * price;
                grandTotal += lineTotal;

                // Update all line total displays for this product
                document.querySelectorAll(`.current-line-total[data-product-id="${productId}"]`).forEach(el => {
                    if (el.tagName === 'TD') {
                        el.textContent = '$' + lineTotal.toFixed(2);
                    } else {
                        el.textContent = '$' + lineTotal.toFixed(2);
                    }
                });
            }
        });

        const gtDesktop = document.getElementById('grandTotalDesktop');
        const gtMobile = document.getElementById('grandTotalMobile');
        if (gtDesktop) gtDesktop.innerHTML = '<strong>$' + grandTotal.toFixed(2) + '</strong>';
        if (gtMobile) gtMobile.textContent = '$' + grandTotal.toFixed(2);
    }

    // Sync desktop/mobile inputs for current products
    document.querySelectorAll('.current-qty-input, .current-price-input').forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.dataset.productId;
            const isQty = this.classList.contains('current-qty-input');
            const selector = isQty ? '.current-qty-input' : '.current-price-input';
            document.querySelectorAll(`${selector}[data-product-id="${productId}"]`).forEach(other => {
                if (other !== this) other.value = this.value;
            });
            recalcTotals();
        });
    });

    // Sync desktop/mobile inputs for new products
    document.querySelectorAll('.new-qty-input, .new-price-input').forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.dataset.productId;
            const isQty = this.classList.contains('new-qty-input');
            const selector = isQty ? '.new-qty-input' : '.new-price-input';
            document.querySelectorAll(`${selector}[data-product-id="${productId}"]`).forEach(other => {
                if (other !== this) other.value = this.value;
            });
        });
    });

    // Remove product button
    document.querySelectorAll('.remove-product-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            // Set quantity to 0 on all inputs for this product
            document.querySelectorAll(`.current-qty-input[data-product-id="${productId}"]`).forEach(input => {
                input.value = 0;
            });
            // Strikethrough the row/card
            const row = document.getElementById('current-row-' + productId);
            if (row) row.style.textDecoration = 'line-through';
            const card = document.getElementById('current-card-' + productId);
            if (card) card.style.opacity = '0.5';
            recalcTotals();
        });
    });
})();
</script>
@endpush
@endif
@endsection
