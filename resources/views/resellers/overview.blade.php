@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.reseller_overview.title') }}</h1>
        <a href="{{ route('resellers.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-list"></i> {{ __('messages.reseller_overview.all_resellers') }}
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.reseller_overview.pending_deliveries') }}</h6>
                    <p class="card-text display-6">{{ $stats['pending_deliveries'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.reseller_overview.pending_reports') }}</h6>
                    <p class="card-text display-6">{{ $stats['pending_reports'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.reseller_overview.unpaid_invoices') }}</h6>
                    <p class="card-text display-6">{{ $stats['unpaid_invoices'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.reseller_overview.unpaid_amount') }}</h6>
                    <p class="card-text display-6">${{ number_format($stats['unpaid_amount'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== LIVRAISONS ================== --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-truck"></i> {{ __('messages.reseller_overview.deliveries') }}</div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="deliveriesTabs" role="tablist">
                @php
                    $deliveryStatuses = [
                        'pending' => __('messages.reseller_overview.pending'),
                        'shipped' => __('messages.reseller_overview.shipped'),
                    ];
                    $deliveriesByStatus = [
                        'pending' => $pendingDeliveries,
                        'shipped' => $shippedDeliveries,
                    ];
                @endphp
                @foreach($deliveryStatuses as $key => $label)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                id="delivery-{{ $key }}-tab" data-bs-toggle="tab"
                                data-bs-target="#delivery-{{ $key }}" type="button" role="tab">
                            {{ $label }}
                            <span class="badge bg-{{ $key == 'pending' ? 'warning' : 'success' }}">
                                {{ $deliveriesByStatus[$key]->count() }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content mt-3" id="deliveriesTabsContent">
                @foreach($deliveryStatuses as $key => $label)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="delivery-{{ $key }}" role="tabpanel">
                        @if($deliveriesByStatus[$key]->isEmpty())
                            <p class="text-muted text-center py-4">{{ __('messages.reseller_overview.no_data') }}</p>
                        @else
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>{{ __('messages.reseller_overview.reseller') }}</th>
                                        <th>{{ __('messages.reseller_overview.status') }}</th>
                                        <th>{{ __('messages.reseller_overview.products') }}</th>
                                        <th>{{ __('messages.reseller_overview.date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($deliveriesByStatus[$key] as $delivery)
                                        <tr>
                                            <td>
                                                @if($delivery->reseller_id)
                                                    <a href="{{ route('reseller-stock-deliveries.show', [$delivery->reseller_id, $delivery]) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @elseif($delivery->store_id)
                                                    <a href="{{ route('reseller-stock-deliveries.show', ['shop-' . $delivery->store_id, $delivery]) }}" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($delivery->reseller)
                                                    <a href="{{ route('resellers.show', $delivery->reseller_id) }}">{{ $delivery->reseller->name }}</a>
                                                @elseif($delivery->store)
                                                    <a href="{{ route('resellers.show', 'shop-' . $delivery->store_id) }}">{{ $delivery->store->name }}</a>
                                                @endif
                                            </td>
                                            <td>
                                                @if($delivery->status === 'draft')
                                                    <span class="badge bg-secondary">{{ __('messages.reseller_overview.status_draft') }}</span>
                                                @elseif($delivery->status === 'ready_to_ship')
                                                    <span class="badge bg-warning text-dark">{{ __('messages.reseller_overview.status_ready') }}</span>
                                                @elseif($delivery->status === 'shipped')
                                                    <span class="badge bg-success">{{ __('messages.reseller_overview.status_shipped') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $delivery->products->count() }}</td>
                                            <td>{{ $delivery->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================== BUYER INVOICES ================== --}}
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span><i class="bi bi-bag-check"></i> {{ __('messages.reseller_overview.buyer_invoices') }}</span>
            @if($unpaidBuyerInvoices->count() > 0)
                <span class="badge bg-danger">{{ $unpaidBuyerInvoices->count() }} {{ __('messages.reseller_overview.unpaid') }}</span>
            @endif
        </div>
        <div class="card-body">
            @if($unpaidBuyerInvoices->isEmpty())
                <p class="text-muted text-center py-4">{{ __('messages.reseller_overview.no_unpaid_buyer_invoices') }}</p>
            @else
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{{ __('messages.reseller_overview.reseller') }}</th>
                            <th>{{ __('messages.reseller_overview.delivery') }}</th>
                            <th>{{ __('messages.reseller_overview.amount') }}</th>
                            <th>{{ __('messages.reseller_overview.remaining') }}</th>
                            <th>{{ __('messages.reseller_overview.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidBuyerInvoices as $invoice)
                            @php
                                $paid = $invoice->payments ? $invoice->payments->sum('amount') : 0;
                                $remaining = $invoice->total_amount - $paid;
                            @endphp
                            <tr>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('reseller-invoices.show', $invoice) }}">
                                                    <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModalBuyer-{{ $invoice->id }}">
                                                    <i class="bi bi-cash-coin"></i> {{ __('messages.Mark as paid') }}
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                @php
                                    $buyerResellerId = $invoice->reseller_id ?? $invoice->resellerStockDelivery?->reseller_id;
                                @endphp
                                <td>
                                    @if($invoice->reseller)
                                        <a href="{{ route('resellers.show', $invoice->reseller_id) }}">{{ $invoice->reseller->name }}</a>
                                    @elseif($invoice->resellerStockDelivery?->reseller)
                                        <a href="{{ route('resellers.show', $buyerResellerId) }}">{{ $invoice->resellerStockDelivery->reseller->name }}</a>
                                    @endif
                                </td>
                                <td>
                                    @if($invoice->reseller_stock_delivery_id && $buyerResellerId)
                                        <a href="{{ route('reseller-stock-deliveries.edit', ['reseller' => $buyerResellerId, 'delivery' => $invoice->reseller_stock_delivery_id]) }}">
                                            #{{ $invoice->reseller_stock_delivery_id }}
                                        </a>
                                    @endif
                                </td>
                                <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                <td class="text-danger fw-bold">${{ number_format($remaining, 2) }}</td>
                                <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                            </tr>

                            {{-- Modal Mark as Paid --}}
                            <div class="modal fade" id="markAsPaidModalBuyer-{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                    <form action="{{ route('reseller-invoices.markAsPaid', $invoice) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('messages.Mark as paid') }} — {{ $invoice->reseller?->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.Amount paid') }} : <strong>${{ number_format($remaining, 2) }}</strong></label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.general_invoices.payment_date') }} <span class="text-danger">*</span></label>
                                                    <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.Méthode de paiement') }} <span class="text-danger">*</span></label>
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
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.general_invoices.payment_proof') }}</label>
                                                    <input type="file" name="payment_proof" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
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
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- ================== SALE REPORTS (3 onglets) ================== --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-receipt"></i> {{ __('messages.reseller_overview.sales_reports') }}</div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="invoicesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="invoice-waiting-tab" data-bs-toggle="tab"
                            data-bs-target="#invoice-waiting" type="button" role="tab">
                        {{ __('messages.reseller_overview.waiting_invoice') }}
                        <span class="badge bg-warning text-dark">{{ $pendingReports->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="invoice-unpaid-tab" data-bs-toggle="tab"
                            data-bs-target="#invoice-unpaid" type="button" role="tab">
                        {{ __('messages.reseller_overview.invoices_unpaid') }}
                        <span class="badge bg-danger">{{ $unpaidInvoices->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="invoice-paid-tab" data-bs-toggle="tab"
                            data-bs-target="#invoice-paid" type="button" role="tab">
                        {{ __('messages.reseller_overview.invoices_paid') }}
                        <span class="badge bg-success">{{ $paidInvoices->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content mt-3" id="invoicesTabsContent">
                {{-- Onglet: Waiting Invoices (rapports en attente) --}}
                <div class="tab-pane fade show active" id="invoice-waiting" role="tabpanel">
                    @if($pendingReports->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('messages.reseller_overview.no_data') }}</p>
                    @else
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('messages.reseller_overview.reseller') }}</th>
                                    <th>{{ __('messages.reseller_overview.type') }}</th>
                                    <th>{{ __('messages.reseller_overview.items') }}</th>
                                    <th>{{ __('messages.reseller_overview.amount') }}</th>
                                    <th>{{ __('messages.reseller_overview.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingReports as $report)
                                    <tr>
                                        <td>
                                            @if($report->reseller_id)
                                                <a href="{{ route('resellers.reports.show', [$report->reseller_id, $report->id]) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @elseif($report->store_id)
                                                <a href="{{ route('resellers.reports.show', ['shop-' . $report->store_id, $report->id]) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($report->reseller)
                                                <a href="{{ route('resellers.show', $report->reseller_id) }}">{{ $report->reseller->name }}</a>
                                            @elseif($report->store)
                                                <a href="{{ route('resellers.show', 'shop-' . $report->store_id) }}">{{ $report->store->name }}</a>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-secondary">{{ __('messages.reseller_overview.sales_report') }}</span></td>
                                        <td>{{ $report->items->count() }}</td>
                                        <td>${{ number_format($report->totalAmount(), 2) }}</td>
                                        <td>{{ $report->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Onglet: Invoices Unpaid --}}
                <div class="tab-pane fade" id="invoice-unpaid" role="tabpanel">
                    @if($unpaidInvoices->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('messages.reseller_overview.no_data') }}</p>
                    @else
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('messages.reseller_overview.reseller') }}</th>
                                    <th>{{ __('messages.reseller_overview.type') }}</th>
                                    <th>{{ __('messages.reseller_overview.amount') }}</th>
                                    <th>{{ __('messages.reseller_overview.remaining') }}</th>
                                    <th>{{ __('messages.reseller_overview.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unpaidInvoices as $invoice)
                                    @php
                                        $paid = $invoice->payments ? $invoice->payments->sum('amount') : 0;
                                        $remaining = $invoice->total_amount - $paid;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reseller-invoices.show', $invoice) }}">
                                                            <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                                                        </a>
                                                    </li>
                                                    @if($invoice->sales_report_id)
                                                    <li>
                                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModalRI-{{ $invoice->id }}">
                                                            <i class="bi bi-cash-coin"></i> {{ __('messages.Mark as paid') }}
                                                        </button>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            @if($invoice->reseller)
                                                <a href="{{ route('resellers.show', $invoice->reseller_id) }}">{{ $invoice->reseller->name }}</a>
                                            @elseif($invoice->store)
                                                <a href="{{ route('resellers.show', 'shop-' . $invoice->store_id) }}">{{ $invoice->store->name }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($invoice->reseller_stock_delivery_id)
                                                <span class="badge bg-info">{{ __('messages.reseller_overview.delivery') }}</span>
                                            @elseif($invoice->sales_report_id)
                                                <span class="badge bg-secondary">{{ __('messages.reseller_overview.sales_report') }}</span>
                                            @endif
                                        </td>
                                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                        <td class="text-danger fw-bold">${{ number_format($remaining, 2) }}</td>
                                        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                    </tr>

                                    {{-- Modal Mark as Paid --}}
                                    @if($invoice->sales_report_id)
                                    <div class="modal fade" id="markAsPaidModalRI-{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                                            <form action="{{ route('reseller-invoices.markAsPaid', $invoice) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ __('messages.Mark sale report as paid') }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.Amount paid') }} : <strong>${{ number_format($remaining, 2) }}</strong></label>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.general_invoices.payment_date') }} <span class="text-danger">*</span></label>
                                                            <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.Méthode de paiement') }} <span class="text-danger">*</span></label>
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
                                                        <div class="mb-3">
                                                            <label class="form-label">{{ __('messages.general_invoices.payment_proof') }}</label>
                                                            <input type="file" name="payment_proof" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                                            <small class="text-muted">{{ __('messages.general_invoices.payment_proof_hint') }}</small>
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
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Onglet: Invoices Paid --}}
                <div class="tab-pane fade" id="invoice-paid" role="tabpanel">
                    @if($paidInvoices->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('messages.reseller_overview.no_data') }}</p>
                    @else
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ __('messages.reseller_overview.reseller') }}</th>
                                    <th>{{ __('messages.reseller_overview.type') }}</th>
                                    <th>{{ __('messages.reseller_overview.amount') }}</th>
                                    <th>{{ __('messages.reseller_overview.paid_date') }}</th>
                                    <th>{{ __('messages.reseller_overview.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($paidInvoices as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('reseller-invoices.show', $invoice) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                        <td>
                                            @if($invoice->reseller)
                                                <a href="{{ route('resellers.show', $invoice->reseller_id) }}">{{ $invoice->reseller->name }}</a>
                                            @elseif($invoice->store)
                                                <a href="{{ route('resellers.show', 'shop-' . $invoice->store_id) }}">{{ $invoice->store->name }}</a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($invoice->reseller_stock_delivery_id)
                                                <span class="badge bg-info">{{ __('messages.reseller_overview.delivery') }}</span>
                                            @elseif($invoice->sales_report_id)
                                                <span class="badge bg-secondary">{{ __('messages.reseller_overview.sales_report') }}</span>
                                            @endif
                                        </td>
                                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>{{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $invoice->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
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
