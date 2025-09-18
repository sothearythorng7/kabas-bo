{{-- resources/views/supplier_orders/partials/order_actions.blade.php --}}
@props(['order', 'type' => 'order', 'paymentMethods'])

<div class="btn-group">
    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
        <i class="bi bi-three-dots-vertical"></i>
    </button>
    <ul class="dropdown-menu">
        @if($type === 'order')
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
        @elseif($type === 'sale_report')
            <li>
                <a class="dropdown-item" href="{{ route('sale-reports.show', [$order->supplier, $order]) }}">
                    <i class="bi bi-eye-fill"></i> {{ __('messages.btn.view') }}
                </a>
            </li>
            @if($order->status === 'waiting_invoice')
                <li>
                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#markAsPaidModal-{{ $order->id }}">
                        <i class="bi bi-cash-coin"></i> @t("Mark as paid")
                    </button>
                </li>
            @endif
        @endif
    </ul>
</div>
