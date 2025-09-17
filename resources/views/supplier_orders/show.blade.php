@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.show_title') }} - {{ $supplier->name }}</h1>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.supplier_order.general') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.supplier_order.ordered_products') }}
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="orderTabsContent">
        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">

            {{-- Actions --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="btn-group" role="group" aria-label="Actions commande">
                        @if($order->status === 'pending')
                            <a href="{{ route('supplier-orders.edit', [$supplier, $order]) }}" class="btn btn-warning">
                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                            </a>

                            <form action="{{ route('supplier-orders.validate', [$supplier, $order]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle-fill"></i> {{ __('messages.btn.validate') }}
                                </button>
                            </form>
                        @elseif($order->status === 'waiting_reception')
                            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                            </a>
                            <a href="{{ route('supplier-orders.reception', [$supplier, $order]) }}" class="btn btn-info">
                                <i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }}
                            </a>
                        @elseif($order->status === 'waiting_invoice')
                            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                            </a>
                            <a href="{{ route('supplier-orders.invoiceReception', [$supplier, $order]) }}" class="btn btn-secondary">
                                <i class="bi bi-receipt"></i> {{ __('messages.order.invoice_reception') }}
                            </a>
                        @elseif($order->status === 'received')
                            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-primary">
                                <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                            </a>
                        @endif

                        {{-- Lien facture --}}
                        @if($order->invoice_file)
                            <a href="{{ Storage::url($order->invoice_file) }}" target="_blank" class="btn btn-dark">
                                <i class="bi bi-download"></i> @t("Download Invoice")
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Infos générales --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header fw-bold">@t("Order information")</div>
                        <div class="card-body">
                            <p><strong>{{ __('messages.supplier_order.status') }}:</strong>
                                @if($order->status === 'pending')
                                    <span class="badge bg-warning">{{ __('messages.order.pending') }}</span>
                                @elseif($order->status === 'waiting_reception')
                                    <span class="badge bg-info">{{ __('messages.order.waiting_reception') }}</span>
                                @elseif($order->status === 'waiting_invoice')
                                    <span class="badge bg-secondary">{{ __('messages.order.waiting_invoice') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('messages.order.received') }}</span>
                                @endif
                            </p>
                            <p><strong>{{ __('messages.supplier_order.created_at') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>{{ __('messages.supplier_order.destination_store') }}:</strong> {{ $order->destinationStore?->name ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Totaux financiers --}}
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header fw-bold">@t("Financial summary")</div>
                        <div class="card-body">
                            @php
                                $totalTheoretical = null;
                                $totalInvoiced = null;

                                if(in_array($order->status, ['waiting_invoice', 'received', 'waiting_reception'])) {
                                    $totalTheoretical = $order->products->sum(function($product) {
                                        return ($product->pivot->purchase_price ?? 0) * ($product->pivot->quantity_received ?? 0);
                                    });
                                }

                                if ($order->status === 'received') {
                                    $totalInvoiced = $order->products->sum(function($product) {
                                        return ($product->pivot->price_invoiced ?? $product->pivot->purchase_price ?? 0) * ($product->pivot->quantity_received ?? 0);
                                    });
                                }
                            @endphp

                            @if(!is_null($totalTheoretical))
                                <p>
                                    <strong>@t("Total theoretical amount"):</strong>
                                    <span class="badge bg-info">${{ number_format($totalTheoretical, 2) }}</span>
                                </p>
                            @endif

                            @if(!is_null($totalInvoiced))
                                <p>
                                    <strong>@t("Total invoiced amount"):</strong>
                                    <span class="badge bg-primary">${{ number_format($totalInvoiced, 2) }}</span>
                                </p>
                            @endif

                            @if($order->status === 'received')
                                <p>
                                    <strong>@t("Payment status"):</strong>
                                    @if($order->is_paid)
                                        <span class="badge bg-success">@t("Paid")</span>
                                    @else
                                        <span class="badge bg-danger">@t("Unpaid")</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Onglet Produits commandés --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            <div class="card mt-3">
                <div class="card-header fw-bold">{{ __('messages.supplier_order.ordered_products') }}</div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>EAN</th>
                                <th>{{ __('messages.product.name') }}</th>
                                <th>Brand</th>
                                <th>{{ __('messages.product.purchase_price') }}</th>
                                <th>{{ __('messages.supplier_order.price_invoiced') }}</th>
                                <th>{{ __('messages.supplier_order.quantity_ordered') }}</th>
                                <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->products as $product)
                                @php
                                    $orderedPrice = $product->pivot->purchase_price;
                                    $invoicedPrice = ($order->status === 'received')
                                        ? ($product->pivot->price_invoiced ?? $product->pivot->purchase_price ?? null)
                                        : null;

                                    if (is_null($invoicedPrice)) {
                                        $badgeClass = '';
                                        $displayPrice = '-';
                                    } else {
                                        $displayPrice = number_format($invoicedPrice, 2);
                                        if ($invoicedPrice == $orderedPrice) {
                                            $badgeClass = 'bg-success';
                                        } elseif (abs($invoicedPrice - $orderedPrice)/$orderedPrice < 0.05) {
                                            $badgeClass = 'bg-warning';
                                        } else {
                                            $badgeClass = 'bg-danger';
                                        }
                                    }

                                    $quantityReceived = ( in_array($order->status, ['received', 'waiting_invoice']))
                                        ? ($product->pivot->quantity_received ?? '-')
                                        : '-';
                                @endphp
                                <tr>
                                    <td>{{ $product->ean }}</td>
                                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                    <td>{{ $product->brand?->name ?? '-' }}</td>
                                    <td>{{ number_format($orderedPrice, 2) }}</td>
                                    <td>
                                        @if($displayPrice === '-')
                                            -
                                        @else
                                            <span class="badge {{ $badgeClass }}">{{ $displayPrice }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $product->pivot->quantity_ordered }}</td>
                                    <td>{{ $quantityReceived }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>
@endsection
