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
                                            @php $remainingAmount = max(0, $order->invoicedAmount() - $order->deposit); @endphp
                                            <div class="mb-3">
                                                <label class="form-label">
                                                    {{ __('messages.supplier_order.invoiced_amount') }} : <strong>${{ number_format($order->invoicedAmount(), 2) }}</strong>
                                                </label>
                                                @if($order->deposit > 0)
                                                    <br>
                                                    <label class="form-label">
                                                        {{ __('messages.supplier_order.deposit') }} : <span class="text-warning fw-bold">- ${{ number_format($order->deposit, 2) }}</span>
                                                    </label>
                                                    <br>
                                                    <label class="form-label">
                                                        {{ __('messages.supplier_order.remaining_to_pay') }} : <span class="text-success fw-bold">${{ number_format($remainingAmount, 2) }}</span>
                                                    </label>
                                                @endif
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

                {{-- Edit received quantities --}}
                @if(in_array($order->status, ['waiting_invoice', 'received']))
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editQuantitiesModal">
                        <i class="bi bi-pencil-square"></i> {{ __('messages.supplier_order.edit_received_quantities') }}
                    </button>
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
                    <p>
                        <strong>{{ __('messages.supplier_order.deposit') }}:</strong>
                        @if($order->deposit > 0)
                            <span class="badge bg-warning text-dark">- ${{ number_format($order->deposit, 2) }}</span>
                        @else
                            <span class="text-muted">$0.00</span>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#editDepositModal">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </p>
                    @if(in_array($order->status, ['waiting_invoice', 'received']))
                    <p>
                        <strong>{{ __('messages.Total invoiced amount') }}:</strong>
                        <span class="badge bg-primary">${{ number_format($order->invoicedAmount(), 2) }}</span>
                    </p>
                    @endif
                    @if($order->status === 'received')
                    @if($order->deposit > 0)
                    <p>
                        <strong>{{ __('messages.supplier_order.remaining_to_pay') }}:</strong>
                        @php $remainingToPay = $order->invoicedAmount() - $order->deposit; @endphp
                        <span class="badge bg-{{ $remainingToPay <= 0 ? 'success' : 'danger' }}">${{ number_format(max(0, $remainingToPay), 2) }}</span>
                    </p>
                    @endif
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

                                $lineExpected = $orderedPrice * $quantityOrdered;
                                $totalExpected += $lineExpected;

                                $lineTotalInvoiced = $invoicedPrice * $quantityReceived;
                                $totalInvoiced += $lineTotalInvoiced;

                                // Afficher le total facturé si commande reçue/en attente facture, sinon le total attendu
                                $lineTotal = in_array($order->status, ['waiting_invoice', 'received'])
                                    ? $lineTotalInvoiced
                                    : $lineExpected;
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
                            <td>${{ number_format(in_array($order->status, ['waiting_invoice', 'received']) ? $totalInvoiced : $totalExpected, 2) }}</td>
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

{{-- Modal Edit Deposit --}}
<div class="modal fade" id="editDepositModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <form action="{{ route('supplier-orders.updateDeposit', [$supplier, $order]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.supplier_order.edit_deposit') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="deposit" class="form-label">{{ __('messages.supplier_order.deposit') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.00001" min="0" name="deposit" id="deposit" class="form-control" value="{{ $order->deposit }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.general_invoices.payment_date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="deposit_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.Méthode de paiement') }} <span class="text-danger">*</span></label>
                        <select name="deposit_payment_method_id" class="form-select form-select-sm" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.Payment reference') }}</label>
                        <input type="text" name="deposit_reference" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit Received Quantities --}}
@if(in_array($order->status, ['waiting_invoice', 'received']))
<div class="modal fade" id="editQuantitiesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-warning">
            <form action="{{ route('supplier-orders.updateRawMaterialReceivedQuantities', [$supplier, $order]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        {{ __('messages.supplier_order.edit_received_quantities') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        {{ __('messages.supplier_order.edit_quantities_warning') }}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.factory.sku') }}</th>
                                    <th>{{ __('messages.common.name') }}</th>
                                    <th>{{ __('messages.factory.unit') }}</th>
                                    <th class="text-center">{{ __('messages.factory.qty_ordered') }}</th>
                                    <th class="text-center">{{ __('messages.supplier_order.received_quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->rawMaterials as $material)
                                    <tr>
                                        <td><small class="text-muted">{{ $material->sku ?? '-' }}</small></td>
                                        <td>{{ $material->name }}</td>
                                        <td>{{ $material->unit }}</td>
                                        <td class="text-center">{{ number_format($material->pivot->quantity_ordered, 2) }}</td>
                                        <td style="width: 120px;">
                                            <input type="number"
                                                   name="raw_materials[{{ $material->id }}]"
                                                   class="form-control form-control-sm text-center"
                                                   value="{{ $material->pivot->quantity_received ?? 0 }}"
                                                   min="0"
                                                   step="0.01"
                                                   required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> {{ __('messages.btn.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i> {{ __('messages.btn.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
