@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_orders.overview_title') }}</h1>

    {{-- Montant cumulé prévisionnel --}}
    <div class="alert alert-info">
        <strong>{{ __('messages.supplier_orders.total_expected_amount') }}:</strong>
        ${{ number_format($totalPendingAmount, 2) }}
    </div>

    {{-- Montant cumulé des commandes reçues non payées --}}
    <div class="alert alert-warning">
        <strong>@t("Total unpaid received orders"):</strong>
        ${{ number_format($totalUnpaidReceivedAmount, 2) }}
    </div>

    {{-- Onglets par statut --}}
    <ul class="nav nav-tabs" id="ordersTabs" role="tablist">
        @php
            $statuses = [
                'pending' => __('messages.order.pending'),
                'waiting_reception' => __('messages.order.waiting_reception'),
                'waiting_invoice' => __('messages.order.waiting_invoice'),
                'received_unpaid' => __('messages.order.received') . ' - ' . __('messages.order.unpaid'),
                'received_paid' => __('messages.order.received') . ' - ' . __('messages.order.paid'),
            ];
        @endphp
        @foreach($statuses as $key => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                        id="{{ $key }}-tab" data-bs-toggle="tab" 
                        data-bs-target="#{{ $key }}" type="button" role="tab" 
                        aria-controls="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $label }}
                    <span class="badge bg-{{ in_array($key, ['pending','waiting_invoice']) ? 'warning' : ($key=='waiting_reception' ? 'info' : ($key=='received_unpaid' ? 'danger' : 'success')) }}">
                        {{ $ordersByStatus[$key]->total() }}
                    </span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content mt-3" id="ordersTabsContent">
        @foreach($statuses as $key => $label)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                 id="{{ $key }}" role="tabpanel" aria-labelledby="{{ $key }}-tab">
                
                <table class="table table-striped table-hover">
<thead>
    <tr>
        <th></th>
        <th>{{ __('messages.supplier.name') }}</th>
        <th>{{ __('messages.supplier_order.created_at') }}</th>
        <th>Destination</th>
        @if(in_array($key, ['waiting_invoice','received_unpaid','received_paid']))
            <th>@t("Total ordered")</th>
            <th>@t("Total received")</th>
        @endif
        <th>@t("Theoretical amount")</th>
        @if(in_array($key, ['received_unpaid','received_paid']))
            <th>@t("Total billed")</th>
            <th>@t("Paid")</th>
        @endif
    </tr>
</thead>
<tbody>
    @foreach($ordersByStatus[$key] as $order)
        <tr>
            {{-- Bouton d'action --}}
            <td>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="{{ route('supplier-orders.show', [$order->supplier, $order]) }}">
                                <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                            </a>
                        </li>
                        @if($order->status === 'pending')
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.edit', [$order->supplier, $order]) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('supplier-orders.validate', [$order->supplier, $order]) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button class="dropdown-item" type="submit">
                                        <i class="bi bi-check-circle-fill"></i> {{ __('messages.btn.validate') }}
                                    </button>
                                </form>
                            </li>
                        @elseif($order->status === 'waiting_reception')
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$order->supplier, $order]) }}">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.reception', [$order->supplier, $order]) }}">
                                    <i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }}
                                </a>
                            </li>
                        @elseif($order->status === 'waiting_invoice')
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$order->supplier, $order]) }}">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.invoiceReception', [$order->supplier, $order]) }}">
                                    <i class="bi bi-receipt"></i> {{ __('messages.order.invoice_reception') }}
                                </a>
                            </li>
                        @elseif($order->status === 'received' && $order->is_paid == false)
                            <li>
                                <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$order->supplier, $order]) }}">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                </a>
                            </li>
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $order->id }}">
                                    <i class="bi bi-cash-coin"></i> @t("Mark as paid")
                                </button>
                            </li>
                        @endif
                    </ul>
                </div>
            </td>

            {{-- Colonnes restantes --}}
            <td>{{ $order->supplier->name }}</td>
            <td>{{ $order->created_at->format('d/m/Y') }}</td>
            <td>{{ $order->destinationStore?->name ?? '-' }}</td>

            @if(in_array($key, ['waiting_invoice','received_unpaid','received_paid']))
                <td>{{ $order->totalOrdered }}</td>
                <td>{{ $order->totalReceived }}</td>
            @endif

            <td>${{ number_format($order->totalAmount, 2) }}</td>

            @if(in_array($key, ['received_unpaid','received_paid']))
                <td>${{ number_format($order->totalInvoiced, 2) }}</td>
                <td>
                    @if($order->is_paid)
                        <span class="badge bg-success">@t("Yes")</span>
                    @else
                        <span class="badge bg-danger">@t("No")</span>
                    @endif
                </td>
            @endif
        </tr>

        {{-- Modal Mark as Paid --}}
        <div class="modal fade" id="markAsPaidModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <form action="{{ route('supplier-orders.markAsPaid', [$order->supplier, $order]) }}" method="POST">
              @csrf
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">@t("Mark order as paid")</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">@t("Amount paid")</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ $order->totalInvoiced }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">@t("Payment method")</label>
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">@t("Payment reference")</label>
                        <input type="text" name="payment_reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                  <button type="submit" class="btn btn-success">@t("Confirm payment")</button>
                </div>
              </div>
            </form>
          </div>
        </div>
    @endforeach
</tbody>

                </table>

                {{-- Pagination --}}
                {{ $ordersByStatus[$key]->appends(request()->query())->withQueryString()->fragment($key)->links() }}

            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    function showTabFromHash() {
        var hash = window.location.hash;
        if(hash) {
            var tabTriggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
            if(tabTriggerEl) {
                new bootstrap.Tab(tabTriggerEl).show();
            }
        }
    }
    showTabFromHash();

    var tabButtons = document.querySelectorAll('#ordersTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn){
        btn.addEventListener('shown.bs.tab', function(e){
            history.replaceState(null, null, e.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
@endpush
