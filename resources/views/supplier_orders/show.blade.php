@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.show_title') }} - {{ $supplier->name }}</h1>

    <p><strong>{{ __('messages.supplier_order.status') }}:</strong>
        @if($order->status === 'pending')
            <span class="badge bg-warning">{{ __('messages.order.pending') }}</span>
        @elseif($order->status === 'waiting_reception')
            <span class="badge bg-info">{{ __('messages.order.waiting_reception') }}</span>
        @else
            <span class="badge bg-success">{{ __('messages.order.received') }}</span>
        @endif
    </p>

    <p><strong>{{ __('messages.supplier_order.created_at') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>

    {{-- Liste produits --}}
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>Brand</th>
                    <th>{{ __('messages.product.purchase_price') }}</th>
                    <th>{{ __('messages.product.price') }}</th>
                    <th>{{ __('messages.supplier_order.quantity_ordered') }}</th>
                    <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                    <tr>
                        <td>{{ $product->ean }}</td>
                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>
                        <td>{{ number_format($product->pivot->purchase_price, 2) }}</td>
                        <td>{{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->pivot->quantity_ordered }}</td>
                        <td>{{ $product->pivot->quantity_received ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.btn.back') }}
        </a>

        @if($order->status === 'pending')
            <form action="{{ route('supplier-orders.validate', [$supplier, $order]) }}" method="POST" class="d-inline">
                @csrf
                @method('PUT')
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle-fill"></i> {{ __('messages.supplier_order.validate') }}
                </button>
            </form>
        @endif

        @if(in_array($order->status, ['waiting_reception', 'received']))
            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('messages.supplier_order.pdf') }}
            </a>
        @endif
    </div>
</div>
@endsection
