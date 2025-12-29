@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-box-seam"></i> {{ __('messages.supplier_order.show_title') }} - {{ $supplier->name }}</h1>
    <p class="text-muted"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials_order') }}</p>

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
                    <a href="{{ route('supplier-orders.reception', [$supplier, $order]) }}" class="btn btn-info">
                        <i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }}
                    </a>
                @elseif($order->status === 'waiting_invoice')
                    <a href="{{ route('supplier-orders.invoiceReception', [$supplier, $order]) }}" class="btn btn-secondary">
                        <i class="bi bi-receipt"></i> {{ __('messages.order.invoice_reception') }}
                    </a>
                @elseif($order->status === 'received')
                    @if(!$order->is_paid)
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $order->id }}">
                            <i class="bi bi-cash-stack"></i> {{ __('messages.Mark order as paid') }}
                        </button>

                        {{-- Modal Mark as Paid --}}
                        <div class="modal fade" id="markAsPaidModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                                <form action="{{ route('supplier-orders.markAsPaid', [$supplier, $order]) }}" method="POST">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ __('messages.Mark order as paid') }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    {{ __('messages.Amount paid') }} : <strong>${{ number_format($order->invoicedAmount(), 2) }}</strong>
                                                </label>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.Méthode de paiement') }}</label>
                                                <select name="payment_method_id" class="form-select form-select-sm" required>
                                                    @foreach($paymentMethods as $method)
                                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">{{ __('messages.Payment reference') }}</label>
                                                <input type="text" name="payment_reference" class="form-control form-control-sm">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                            <button type="submit" class="btn btn-success btn-sm">{{ __('messages.Confirm payment') }}</button>
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
                        <i class="bi bi-download"></i> {{ __('messages.Download Invoice') }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Infos générales --}}
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header fw-bold">{{ __('messages.Order information') }}</div>
                <div class="card-body">
                    <p><strong>{{ __('messages.common.status') }}:</strong>
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
                    @if($order->validated_at)
                        <p><strong>{{ __('messages.Validated at') }}:</strong> {{ $order->validated_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($order->received_at)
                        <p><strong>{{ __('messages.Received at') }}:</strong> {{ $order->received_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Totaux financiers --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header fw-bold">{{ __('messages.Financial summary') }}</div>
                <div class="card-body">
                    <p>
                        <strong>{{ __('messages.Total theoretical amount') }}:</strong>
                        <span class="badge bg-info">${{ number_format($order->expectedAmount(), 2) }}</span>
                    </p>
                    @if(in_array($order->status, ['waiting_invoice', 'received']))
                    <p>
                        <strong>{{ __('messages.Total invoiced amount') }}:</strong>
                        <span class="badge bg-primary">${{ number_format($order->invoicedAmount(), 2) }}</span>
                    </p>
                    @endif
                    @if($order->status === 'received')
                    <p>
                        <strong>{{ __('messages.Payment status') }}:</strong>
                        @if($order->is_paid)
                            <span class="badge bg-success">{{ __('messages.Paid') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('messages.Unpaid') }}</span>
                        @endif
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Matières premières commandées --}}
    <div class="card mb-3">
        <div class="card-header fw-bold">
            <i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials') }}
            <span class="badge bg-secondary ms-2">{{ $order->rawMaterials->count() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.factory.sku') }}</th>
                            <th>{{ __('messages.common.name') }}</th>
                            <th>{{ __('messages.factory.unit') }}</th>
                            <th>{{ __('messages.factory.purchase_price') }}</th>
                            @if(in_array($order->status, ['waiting_invoice', 'received']))
                                <th>{{ __('messages.supplier_order.price_invoiced') }}</th>
                            @endif
                            <th>{{ __('messages.factory.qty_ordered') }}</th>
                            @if(in_array($order->status, ['waiting_invoice', 'received']))
                                <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                            @endif
                            <th>{{ __('messages.Total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalExpected = 0; $totalInvoiced = 0; @endphp
                        @foreach($order->rawMaterials as $material)
                            @php
                                $orderedPrice = $material->pivot->purchase_price;
                                $quantityOrdered = $material->pivot->quantity_ordered;
                                $quantityReceived = $material->pivot->quantity_received ?? 0;
                                $invoicedPrice = $material->pivot->invoice_price ?? $orderedPrice;

                                $lineTotal = $orderedPrice * $quantityOrdered;
                                $totalExpected += $lineTotal;

                                if(in_array($order->status, ['waiting_invoice', 'received'])) {
                                    $lineTotalInvoiced = $invoicedPrice * $quantityReceived;
                                    $totalInvoiced += $lineTotalInvoiced;
                                }
                            @endphp
                            <tr>
                                <td>{{ $material->sku ?? '-' }}</td>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->unit }}</td>
                                <td>${{ number_format($orderedPrice, 2) }}</td>
                                @if(in_array($order->status, ['waiting_invoice', 'received']))
                                    <td>
                                        @if($invoicedPrice != $orderedPrice)
                                            <span class="badge {{ $invoicedPrice > $orderedPrice ? 'bg-danger' : 'bg-success' }}">
                                                ${{ number_format($invoicedPrice, 2) }}
                                            </span>
                                        @else
                                            ${{ number_format($invoicedPrice, 2) }}
                                        @endif
                                    </td>
                                @endif
                                <td>{{ number_format($quantityOrdered, 2) }}</td>
                                @if(in_array($order->status, ['waiting_invoice', 'received']))
                                    <td>
                                        @if($quantityReceived != $quantityOrdered)
                                            <span class="badge {{ $quantityReceived < $quantityOrdered ? 'bg-warning' : 'bg-info' }}">
                                                {{ number_format($quantityReceived, 2) }}
                                            </span>
                                        @else
                                            {{ number_format($quantityReceived, 2) }}
                                        @endif
                                    </td>
                                @endif
                                <td>${{ number_format($lineTotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="{{ in_array($order->status, ['waiting_invoice', 'received']) ? 7 : 5 }}"></td>
                            <td>${{ number_format($totalExpected, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('factory.suppliers.edit', $supplier) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>
@endsection
