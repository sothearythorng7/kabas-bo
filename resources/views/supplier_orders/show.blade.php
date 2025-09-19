@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.show_title') }} - {{ $supplier->name }}</h1>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="orderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.supplier_order.general') }}
                <span class="badge bg-secondary ms-2">{{ $order->products->count() + $order->priceDifferences->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.supplier_order.ordered_products') }}
                <span class="badge bg-secondary ms-2">{{ $order->products->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="price-diff-tab" data-bs-toggle="tab" data-bs-target="#price-diff" type="button" role="tab" aria-controls="price-diff" aria-selected="false">
                @t("Price differences")
                <span class="badge bg-secondary ms-2">{{ $order->priceDifferences->count() }}</span>
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
                            @if(!$order->is_paid)
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $order->id }}">
                                    <i class="bi bi-cash-stack"></i> @t("Mark order as paid")
                                </button>

                                {{-- Modal Mark as Paid --}}
                                <div class="modal fade" id="markAsPaidModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                                        <form action="{{ route('supplier-orders.markAsPaid', [$supplier, $order]) }}" method="POST">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">@t("Mark order as paid")</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">
                                                            @t("Amount paid") : <strong>${{ $order->invoicedAmount() }}</strong>
                                                        </label>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">@t("Méthode de paiement")</label>
                                                        <select name="payment_method_id" class="form-select form-select-sm" required>
                                                            @foreach($paymentMethods as $method)
                                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">@t("Payment reference")</label>
                                                        <input type="text" name="payment_reference" class="form-control form-control-sm">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                                    <button type="submit" class="btn btn-success btn-sm">@t("Confirm payment")</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
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
                                <p>
                                    <strong>@t("Total theoretical amount"):</strong>
                                    <span class="badge bg-info">${{ number_format($order->expectedAmount(), 2) }}</span>
                                </p>
                                @if($order->status === 'received')
                                <p>
                                    <strong>@t("Total invoiced amount"):</strong>
                                    <span class="badge bg-primary">${{ number_format($order->invoicedAmount(), 2) }}</span>
                                </p>
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
                                <th>@t("quantity ordered")</th>
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

        {{-- Onglet Écarts de prix --}}
        <div class="tab-pane fade" id="price-diff" role="tabpanel" aria-labelledby="price-diff-tab">
            <div class="card mt-3">
                <div class="card-header fw-bold">@t("Price differences")</div>
                <div class="card-body">
                    @if($order->priceDifferences->isEmpty())
                        <p class="text-muted">@t("No price differences recorded.")</p>
                    @else
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>EAN</th>
                                    <th>{{ __('messages.product.name') }}</th>
                                    <th>Brand</th>
                                    <th>@t("Reference price")</th>
                                    <th>@t("Invoiced price")</th>
                                    <th>@t("Update reference?")</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->priceDifferences as $line)
                                    <tr>
                                        <td>{{ $line->product->ean }}</td>
                                        <td>{{ $line->product->name[app()->getLocale()] ?? reset($line->product->name) }}</td>
                                        <td>{{ $line->product->brand?->name ?? '-' }}</td>
                                        <td>{{ number_format($line->reference_price, 2) }}</td>
                                        <td>{{ number_format($line->invoiced_price, 2) }}</td>
                                        <td>
                                            @if($line->update_reference)
                                                <span class="badge bg-success">@t("Yes")</span>
                                            @else
                                                <span class="badge bg-secondary">@t("No")</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
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
