@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ __('messages.resellers.deliveries') }} #{{ $delivery->id }}</h1>
        <div>
            <a href="{{ route('reseller-stock-deliveries.edit', [$reseller->id, $delivery->id]) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
            </a>
            <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
            </a>
        </div>
    </div>

    @php
        $resellerType = $delivery->getResellerType();
        $productsCount = $delivery->products->count();
        if($resellerType == 'buyer')
        {
            $totalPaid = $delivery->invoice?->payments->sum('amount') ?? 0;
            $remaining = max(($delivery->invoice?->total_amount ?? 0) - $totalPaid, 0);
            $paymentsCount = $delivery->invoice?->payments->count() ?? 0;

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
        @if(isset($resellerType) && $resellerType == 'buyer')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                {{ __('messages.resellers.payments') }} <span class="badge bg-secondary">{{ $paymentsCount ?? 0 }}</span>
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
        {{-- Onglet General --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">

            {{-- Infos paiement --}}
            @if(isset($resellerType) && $resellerType == 'buyer' && $delivery->invoice)
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

            {{-- Informations generales --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ __('messages.resellers.delivery_details') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('messages.resellers.recipient') }}:</strong><br>
                            {{ $reseller->name }}
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('messages.resellers.status') }}:</strong><br>
                            <span class="badge bg-{{ $delivery->status === 'shipped' ? 'success' : ($delivery->status === 'draft' ? 'secondary' : 'warning') }}">
                                {{ __('messages.order.' . strtolower($delivery->status)) }}
                            </span>
                        </div>
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('messages.resellers.date') }}:</strong><br>
                            {{ $delivery->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if($delivery->delivered_at)
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('messages.resellers.delivered_at') }}:</strong><br>
                            {{ \Carbon\Carbon::parse($delivery->delivered_at)->format('d/m/Y') }}
                        </div>
                        @endif
                        @if($delivery->shipping_cost > 0)
                        <div class="col-md-4 mb-3">
                            <strong>{{ __('messages.resellers.shipping_cost') }}:</strong><br>
                            ${{ number_format($delivery->shipping_cost, 2) }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Delivery Note Section --}}
            <div class="card mb-4">
                <div class="card-header">
                    <strong><i class="bi bi-file-earmark-text"></i> {{ __('messages.resellers.delivery_note') }}</strong>
                </div>
                <div class="card-body">
                    @if($delivery->delivery_note)
                        <div class="alert alert-success d-flex align-items-center justify-content-between mb-0">
                            <div>
                                <i class="bi bi-check-circle me-2"></i>
                                {{ __('messages.resellers.delivery_note_uploaded_status') }}
                            </div>
                            <div>
                                <a href="{{ asset('storage/' . $delivery->delivery_note) }}" target="_blank" class="btn btn-sm btn-primary me-2">
                                    <i class="bi bi-eye"></i> {{ __('messages.btn.view') }}
                                </a>
                                <a href="{{ asset('storage/' . $delivery->delivery_note) }}" download class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download"></i> {{ __('messages.btn.download') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ __('messages.resellers.no_delivery_note') }}
                        </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('reseller-stock-deliveries.generate-note', [$reseller->id, $delivery->id]) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.resellers.generate_pdf') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
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
                                <td>{{ $product->pivot->quantity }}</td>
                                <td>${{ number_format($product->pivot->unit_price, 2) }}</td>
                                <td>${{ number_format($lineTotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="3" class="text-end"><strong>{{ __('messages.resellers.total') }}</strong></td>
                            <td><strong>{{ $totalQty }}</strong></td>
                            <td></td>
                            <td><strong>${{ number_format($grandTotal, 2) }}</strong></td>
                        </tr>
                    </tfoot>
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
                                    <p class="mb-1"><strong>{{ __('messages.product.price_btob') }}:</strong> ${{ number_format($product->pivot->unit_price, 2) }}</p>
                                    <p class="mb-0"><strong>{{ __('messages.resellers.total_value') }}:</strong> ${{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if(isset($resellerType) && $resellerType == 'buyer')
        {{-- Onglet Paiements --}}
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
            @if(($paymentsCount ?? 0) > 0)
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
                                    <td>${{ number_format($payment->amount, 2) }}</td>
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
                                        <p class="mb-1"><strong>{{ __('messages.resellers.date') }}:</strong> {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.amount') }}:</strong> ${{ number_format($payment->amount, 2) }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.method') }}:</strong> {{ ucfirst($payment->payment_method) }}</p>
                                        <p class="mb-1"><strong>{{ __('messages.resellers.reference') }}:</strong> {{ $payment->reference }}</p>
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
        </div>
        @endif

        {{-- Onglet Invoice --}}
        <div class="tab-pane fade" id="invoice" role="tabpanel" aria-labelledby="invoice-tab">
            <h4><i class="bi bi-receipt"></i> {{ __('messages.resellers.invoice_details') }}</h4>

            {{-- Resume de la livraison --}}
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
                                @php $invoiceTotal = 0; $invoiceTotalQty = 0; @endphp
                                @foreach($delivery->products as $product)
                                    @php
                                        $lineTotal = $product->pivot->quantity * $product->pivot->unit_price;
                                        $invoiceTotal += $lineTotal;
                                        $invoiceTotalQty += $product->pivot->quantity;
                                    @endphp
                                    <tr>
                                        <td>{{ $product->ean }}</td>
                                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td class="text-end">${{ number_format($product->pivot->unit_price, 2) }}</td>
                                        <td class="text-center">{{ $product->pivot->quantity }}</td>
                                        <td class="text-end">${{ number_format($lineTotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>{{ __('messages.resellers.total') }}</strong></td>
                                    <td class="text-center"><strong>{{ $invoiceTotalQty }}</strong></td>
                                    <td class="text-end"><strong>${{ number_format($invoiceTotal, 2) }}</strong></td>
                                </tr>
                                @if($delivery->shipping_cost > 0)
                                <tr>
                                    <td colspan="5" class="text-end">{{ __('messages.resellers.shipping_cost') }}</td>
                                    <td class="text-end">${{ number_format($delivery->shipping_cost, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>{{ __('messages.resellers.grand_total') }}</strong></td>
                                    <td class="text-end"><strong>${{ number_format($invoiceTotal + $delivery->shipping_cost, 2) }}</strong></td>
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
@endsection
