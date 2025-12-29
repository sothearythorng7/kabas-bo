@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.financial.invoices_overview') }}</h1>

    {{-- Alerte total global --}}
    <div class="alert alert-warning mb-3">
        <strong>{{ __('messages.financial.total_amount_to_pay') }}:</strong> ${{ number_format($totalUnpaidAmount, 2) }}
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs" id="invoiceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="supplier-invoices-tab" data-bs-toggle="tab" data-bs-target="#supplier-invoices" type="button" role="tab" aria-controls="supplier-invoices" aria-selected="true">
                {{ __('messages.financial.supplier_invoices') }}
                <span class="badge bg-warning">{{ $orders->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="general-invoices-tab" data-bs-toggle="tab" data-bs-target="#general-invoices" type="button" role="tab" aria-controls="general-invoices" aria-selected="false">
                {{ __('messages.financial.general_invoices') }}
                <span class="badge bg-info">{{ $generalInvoices->total() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="invoiceTabsContent">
        {{-- Onglet Factures fournisseurs --}}
        <div class="tab-pane fade show active" id="supplier-invoices" role="tabpanel" aria-labelledby="supplier-invoices-tab">
            <div class="alert alert-light">
                {{ __('messages.financial.subtotal') }}: ${{ number_format($totalSupplierAmount, 2) }}
                ({{ $orders->total() }} {{ __('messages.financial.invoice_count') }})
            </div>

            @if($orders->count() > 0)
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('messages.financial.supplier') }}</th>
                                <th>{{ __('messages.financial.destination_store') }}</th>
                                <th>{{ __('messages.financial.reception_date') }}</th>
                                <th>{{ __('messages.financial.invoiced_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                @php
                                    $totalInvoice = $order->products->sum(fn($p) =>
                                        ($p->pivot->invoice_price ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_received ?? 0)
                                    );
                                @endphp
                                <tr>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-start">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('supplier-orders.show', [$order->supplier, $order]) }}">
                                                        {{ __('messages.financial.view') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>{{ $order->supplier->name }}</td>
                                    <td>{{ $order->destinationStore?->name ?? '-' }}</td>
                                    <td>{{ $order->updated_at->format('d/m/Y') }}</td>
                                    <td>${{ number_format($totalInvoice, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $orders->links() }}
            @else
                <p class="text-muted">{{ __('messages.financial.no_supplier_invoices') }}</p>
            @endif
        </div>

        {{-- Onglet Factures générales --}}
        <div class="tab-pane fade" id="general-invoices" role="tabpanel" aria-labelledby="general-invoices-tab">
            <div class="alert alert-light">
                {{ __('messages.financial.subtotal') }}: ${{ number_format($totalGeneralAmount, 2) }}
                ({{ $generalInvoices->total() }} {{ __('messages.financial.invoice_count') }})
            </div>

            @if($generalInvoices->count() > 0)
                <div class="table-responsive" style="overflow: visible;">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('messages.financial.label') }}</th>
                                <th>{{ __('messages.financial.store') }}</th>
                                <th>{{ __('messages.financial.category') }}</th>
                                <th>{{ __('messages.financial.due_date') }}</th>
                                <th>{{ __('messages.financial.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($generalInvoices as $invoice)
                                <tr>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-start">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('financial.general-invoices.show', [$invoice->store_id, $invoice->id]) }}">
                                                        {{ __('messages.financial.view') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('financial.general-invoices.edit', [$invoice->store_id, $invoice->id]) }}">
                                                        {{ __('messages.financial.edit') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>{{ $invoice->label }}</td>
                                    <td>{{ $invoice->store?->name ?? '-' }}</td>
                                    <td>
                                        @if($invoice->category)
                                            <span class="badge" style="background-color: {{ $invoice->category->color }}">
                                                {{ $invoice->category->name }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($invoice->due_date)
                                            @php $isOverdue = $invoice->due_date->isPast(); @endphp
                                            <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                                @if($isOverdue)<i class="bi bi-exclamation-triangle-fill"></i>@endif
                                                {{ $invoice->due_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>${{ number_format($invoice->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $generalInvoices->links() }}
            @else
                <p class="text-muted">{{ __('messages.financial.no_general_invoices') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Show tab according to URL hash
    var hash = window.location.hash;
    if(hash) {
        var tabTriggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
        if(tabTriggerEl) {
            var tab = new bootstrap.Tab(tabTriggerEl);
            tab.show();
        }
    }

    // Update hash when tab changes
    var tabButtons = document.querySelectorAll('#invoiceTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn){
        btn.addEventListener('shown.bs.tab', function(e){
            history.replaceState(null, null, e.target.getAttribute('data-bs-target'));
        });
    });
});
</script>
@endpush
