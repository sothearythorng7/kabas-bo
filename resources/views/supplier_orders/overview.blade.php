@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_orders.overview_title') }}</h1>

    {{-- Montant cumulé prévisionnel --}}
    <div class="alert alert-info">
        <strong>{{ __('messages.supplier_orders.total_expected_amount') }}:</strong> ${{ number_format($totalPendingAmount, 2) }}
    </div>

    {{-- Onglets par statut --}}
    <ul class="nav nav-tabs" id="ordersTabs" role="tablist">
        @php
            $statuses = [
                'pending' => __('messages.order.pending'),
                'waiting_reception' => __('messages.order.waiting_reception'),
                'waiting_invoice' => __('messages.order.waiting_invoice'),
                'received' => __('messages.order.received')
            ];
        @endphp
        @foreach($statuses as $key => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                        id="{{ $key }}-tab" data-bs-toggle="tab" 
                        data-bs-target="#{{ $key }}" type="button" role="tab" 
                        aria-controls="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    {{ $label }}
                    <span class="badge bg-{{ in_array($key, ['pending','waiting_invoice']) ? 'warning' : ($key=='waiting_reception' ? 'info' : 'success') }}">
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
                            @if(in_array($key, ['waiting_invoice','received']))
                                <th>@t("Total ordered")</th>
                                <th>@t("Total received")</th>
                            @endif
                            <th>@t("Theoretical amount")</th>
                            @if($key == 'received')
                                <th>@t("Total billed")</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordersByStatus[$key] as $order)
                            @php
                                $items = $order->products ?? collect();
                                $totalOrdered = ($key == 'waiting_invoice' || $key == 'received') 
                                    ? $items->sum(fn($item) => $item->pivot->quantity_ordered ?? 0)
                                    : '-';
                                $totalReceived = ($key == 'received') 
                                    ? $items->sum(fn($item) => $item->pivot->quantity_received ?? 0)
                                    : '-';
                                $totalAmount = $items->sum(fn($item) => ($item->pivot->purchase_price ?? 0) * ($item->pivot->quantity_ordered ?? 0));
                                $totalInvoiced = ($key == 'received')
                                    ? $items->sum(fn($item) => ($item->pivot->price_invoiced ?? $item->pivot->purchase_price ?? 0) * ($item->pivot->quantity_ordered ?? 0))
                                    : null;
                            @endphp
                            <tr>
                                {{-- Bouton d'action dans la première colonne --}}
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
                                            @elseif($order->status === 'received')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('supplier-orders.pdf', [$order->supplier, $order]) }}">
                                                        <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>

                                {{-- Colonnes restantes --}}
                                <td>{{ $order->supplier->name }}</td>
                                <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                <td>{{ $order->destinationStore?->name ?? '-' }}</td>

                                @if(in_array($key, ['waiting_invoice','received']))
                                    <td>{{ $totalOrdered }}</td>
                                    <td>{{ $totalReceived }}</td>
                                @endif

                                <td>${{ number_format($totalAmount, 2) }}</td>

                                @if($key == 'received')
                                    <td>${{ number_format($totalInvoiced, 2) }}</td>
                                @endif
                            </tr>
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
