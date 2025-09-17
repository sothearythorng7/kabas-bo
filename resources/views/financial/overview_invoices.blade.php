@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Invoices Overview")</h1>

    {{-- Tabs --}}
    <ul class="nav nav-tabs" id="invoiceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="unpaid-invoices-tab" data-bs-toggle="tab" data-bs-target="#unpaid-invoices" type="button" role="tab" aria-controls="unpaid-invoices" aria-selected="true">
                Supplier Invoices to Pay
                <span class="badge bg-warning">{{ $orders->total() }}</span>
            </button>
        </li>
        {{-- Future tabs can be added here --}}
    </ul>

    <div class="tab-content mt-3" id="invoiceTabsContent">
        {{-- Unpaid invoices tab --}}
        <div class="tab-pane fade show active" id="unpaid-invoices" role="tabpanel" aria-labelledby="unpaid-invoices-tab">
            <div class="alert alert-warning">
                @t("Total amount remaining to pay"): ${{ number_format($totalUnpaidAmount, 2) }}
                ({{ $orders->total() }} invoice(s))
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th></th> {{-- Dropdown actions --}}
                            <th>@t("Fournisseur")</th>
                            <th>@t("destination_store")</th>
                            <th>@t("Received Date")</th>
                            <th>@t("Invoiced Amount")</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            @php
                                $totalInvoice = $order->products->sum(fn($p) =>
                                    ($p->pivot->price_invoiced ?? $p->pivot->purchase_price ?? 0) * ($p->pivot->quantity_ordered ?? 0)
                                );
                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('supplier-orders.show', [$order->supplier, $order]) }}">
                                                    View
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
        </div>

        {{-- Future tabs can be added here --}}
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
