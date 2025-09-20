@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_orders.overview_title') }}</h1>

    {{-- Totaux en cards --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">@t("Total order expected amount")</h5>
                    <p class="card-text display-5">${{ number_format($totalPendingAmount, 2) }}</p>
                    <small>@t("Total amount of orders waiting delivery or waiting invoice")</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">@t("Total unpaid received orders")</h5>
                    <p class="card-text display-5">${{ number_format($totalUnpaidReceivedAmount, 2) }}</p>
                    <small>@t("Total amount of orders received and not paid")</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title">@t("Total unpaid invoiced sale reports")</h5>
                    <p class="card-text display-5">${{ number_format($totalUnpaidInvoicedSaleReportAmount, 2) }}</p>
                    <small>Total amount of sales reports not paid</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== COMMANDES ================== --}}
    <div class="card mb-3">
        <div class="card-header"> @t("Supplier Orders (Buyers)")</div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="ordersTabs" role="tablist">
                @php
                    $orderStatuses = [
                        'pending' => __('messages.order.pending'),
                        'waiting_reception' => __('messages.order.waiting_reception'),
                        'waiting_invoice' => __('messages.order.waiting_invoice'),
                        'received_unpaid' => __('messages.order.received') . ' - ' . __('messages.order.unpaid'),
                        'received_paid' => __('messages.order.received') . ' - ' . __('messages.order.paid'),
                    ];
                @endphp
                @foreach($orderStatuses as $key => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                id="order-{{ $key }}-tab" data-bs-toggle="tab" 
                                data-bs-target="#order-{{ $key }}" type="button" role="tab" 
                                aria-controls="order-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $label }}
                            <span class="badge bg-{{ in_array($key, ['pending','waiting_invoice']) ? 'warning' : ($key=='waiting_reception' ? 'info' : ($key=='received_unpaid' ? 'danger' : 'success')) }}">
                                {{ $ordersByStatus[$key]->total() }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mt-3" id="ordersTabsContent">
                @foreach($orderStatuses as $key => $label)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="order-{{ $key }}" role="tabpanel" aria-labelledby="order-{{ $key }}-tab">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('messages.supplier.name') }}</th>
                                    <th>{{ __('messages.supplier_order.created_at') }}</th>
                                    <th>{{ __('messages.supplier_order.destination') }}</th>
                                    @switch($key)
                                        @case('pending')
                                        <th>@t("Total ordered")</th>
                                        <th>@t("Theoretical amount")</th>
                                        @break
                                        @case('waiting_reception')
                                        <th>@t("Total ordered")</th>
                                        <th>@t("Theoretical amount")</th>
                                        @break
                                        @case('waiting_invoice')
                                        <th>@t("Total ordered")</th>
                                        <th>@t("Total received")</th>
                                        <th>@t("Theoretical amount")</th>
                                        @break       
                                        @case('received_unpaid')
                                        <th>@t("Total ordered")</th>
                                        <th>@t("Total received")</th>
                                        <th>@t("Theoretical amount")</th>
                                        <th>@t("Total billed")</th>
                                        <th>@t("Paid")</th>
                                        @break    
                                        @case('received_paid')
                                        <th>@t("Total ordered")</th>
                                        <th>@t("Total received")</th>
                                        <th>@t("Theoretical amount")</th>
                                        <th>@t("Total billed")</th>
                                        <th>@t("Paid")</th>
                                        @break               
                                    @endswitch
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ordersByStatus[$key] as $order)
                                    <tr>
                                        {{-- Actions --}}
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
                                                    @elseif($order->status === 'received' && !$order->is_paid)
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


                                        <td>{{ $order->supplier->name }}</td>
                                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $order->destinationStore?->name ?? '-' }}</td>
                                    @switch($key)
                                        @case('pending')
                                        <td>{{ $order->totalOrdered() }}</td>
                                        <td>${{ number_format($order->expectedAmount(), 2) }}</td>
                                        @break
                                        @case('waiting_reception')
                                        <td>{{ $order->totalOrdered() }}</td>
                                        <td>${{ number_format($order->expectedAmount(), 2) }}</td>
                                        @break
                                        @case('waiting_invoice')
                                        <td>{{ $order->totalOrdered() }}</td>
                                        <td>{{ $order->totalReceived() }}</td>
                                        <td>${{ number_format($order->expectedAmount(), 2) }}</td>
                                        @break       
                                        @case('received_unpaid')
                                        <td>{{ $order->totalOrdered() }}</td>
                                        <td>{{ $order->totalReceived() }}</td>
                                        <td>${{ number_format($order->expectedAmount(), 2) }}</td>
                                        <td>${{ number_format($order->invoicedAmount(), 2) }}</td>
                                        <td>
                                            @if($order->is_paid)
                                                <span class="badge bg-success">@t("Yes")</span>
                                            @else
                                                <span class="badge bg-danger">@t("No")</span>
                                            @endif
                                        </td>
                                        @break    
                                        @case('received_paid')
                                        <td>{{ $order->totalOrdered() }}</td>
                                        <td>{{ $order->totalReceived() }}</td>
                                        <td>${{ number_format($order->expectedAmount(), 2) }}</td>
                                        <td>${{ number_format($order->invoicedAmount(), 2) }}</td>
                                             <td>
                                                @if($order->is_paid)
                                                    <span class="badge bg-success">@t("Yes")</span>
                                                @else
                                                    <span class="badge bg-danger">@t("No")</span>
                                                @endif
                                            </td>
                                        @break               
                                    @endswitch  
                                    </tr>
                                    {{-- Modal Mark as Paid --}}
                                    <div class="modal fade" id="markAsPaidModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                                            <form action="{{ route('supplier-orders.markAsPaid', [$order->supplier, $order]) }}" method="POST">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">@t("Mark order as paid")</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">
                                                                @t("Amount paid") : 
                                                                <strong>${{ $order->invoicedAmount() }}</strong>
                                                            </label>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.Methodes_de_paiement') }}</label>
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
                                @endforeach
                            </tbody>
                        </table>
                        {{ $ordersByStatus[$key]->appends(request()->query())->withQueryString()->fragment("order-".$key)->links() }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================== SALE REPORTS ================== --}}
    <div class="card mb-3">
        <div class="card-header"> @t("Sale Reports")</div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="srTabs" role="tablist">
                @php
                    $srStatuses = [
                        'waiting_invoice' =>  @t("Waiting invoice"),
                        'invoiced_unpaid' => @('Unpaid'),
                        'invoiced_paid' => @t("paid"),
                    ];
                @endphp
                @foreach($srStatuses as $key => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                id="sr-{{ $key }}-tab" data-bs-toggle="tab" 
                                data-bs-target="#sr-{{ $key }}" type="button" role="tab" 
                                aria-controls="sr-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            {{ $label }}
                            <span class="badge bg-{{ $key=='waiting_invoice' ? 'warning' : ($key=='invoiced_unpaid' ? 'danger' : 'success') }}">
                                {{ $saleReportsByStatus[$key]->total() }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mt-3" id="srTabsContent">
                @foreach($srStatuses as $key => $label)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                        id="sr-{{ $key }}" role="tabpanel" aria-labelledby="sr-{{ $key }}-tab">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('messages.store.name') }}</th>
                                    <th>@t("Periode")</th>
                                    <th>@t("Theoretical amount")</th>
                                    @if(in_array($key, ['invoiced_unpaid','invoiced_paid']))
                                        <th>@t("Total billed")</th>
                                        <th>@t("Paid")</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($saleReportsByStatus[$key] as $report)
                                    <tr>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('sale-reports.show', [$report->supplier, $report]) }}">
                                                            <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                                        </a>
                                                    </li>
                                                    @if($report->status === 'waiting_invoice')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('sale-reports.invoiceReception', [$report->supplier, $report]) }}">
                                                                <i class="bi bi-receipt"></i> @t("Invoice reception")
                                                            </a>
                                                        </li>
                                                    @elseif($report->status === 'invoiced' && !$report->is_paid)
                                                        <li>
                                                            <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModalSR-{{ $report->id }}">
                                                                <i class="bi bi-cash-coin"></i> @t("Mark as paid")
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>

                                        <td>{{ $report->store->name }}</td>
                                        <td>{{ $report->period_start->format('d/m/Y') }} - {{ $report->period_end->format('d/m/Y') }}</td>
                                        <td>${{ number_format($report->total_amount_theoretical, 2) }}</td>
                                        @if(in_array($key, ['invoiced_unpaid','invoiced_paid']))
                                            <td>${{ number_format($report->total_amount_invoiced, 2) }}</td>
                                            <td>
                                                @if($report->is_paid)
                                                    <span class="badge bg-success">@t("Yes")</span>
                                                @else
                                                    <span class="badge bg-danger">@t("No")</span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>

                                    {{-- Modal Mark as Paid Sale Report --}}
                                    <div class="modal fade" id="markAsPaidModalSR-{{ $report->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                                            <form action="{{ route('sale-reports.markAsPaid', [$report->supplier, $report]) }}" method="POST">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">@t("Mark sale report as paid")</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">@t("Amount paid") : <strong>${{ $report->total_amount_invoiced }}</strong></label>
                                                           
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.Methodes_de_paiement') }}</label>
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
                        {{ $saleReportsByStatus[$key]->appends(request()->query())->withQueryString()->fragment("sr-".$key)->links() }}
                    </div>
                @endforeach
            </div>
        </div>
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

    var tabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn){
        btn.addEventListener('shown.bs.tab', function(e){
            history.replaceState(null, null, e.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
@endpush
