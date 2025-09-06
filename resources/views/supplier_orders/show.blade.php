@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.show_title') }} - {{ $supplier->name }}</h1>

    <p><strong>{{ __('messages.supplier_order.status') }}:</strong> {{ ucfirst($order->status) }}</p>
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
                    @if($order->status === 'waiting_for_reception')
                        <th>{{ __('messages.supplier_order.quantity_received') }}</th>
                    @endif
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
                    @if($order->status === 'waiting_for_reception')
                        <td>{{ $product->pivot->quantity_received ?? $product->pivot->quantity_ordered }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Version mobile --}}
    <div class="d-md-none">
        <div class="row">
            @foreach($order->products as $product)
            <div class="col-12 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <h5 class="card-title">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                        <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                        <p class="mb-1"><strong>Brand:</strong> {{ $product->brand?->name ?? '-' }}</p>
                        <p class="mb-1"><strong>{{ __('messages.product.purchase_price') }}:</strong> {{ number_format($product->pivot->purchase_price, 2) }}</p>
                        <p class="mb-1"><strong>{{ __('messages.product.price') }}:</strong> {{ number_format($product->price, 2) }}</p>
                        <p class="mb-1"><strong>{{ __('messages.supplier_order.quantity_ordered') }}:</strong> {{ $product->pivot->quantity_ordered }}</p>
                        @if($order->status === 'waiting_for_reception')
                        <p class="mb-1"><strong>{{ __('messages.supplier_order.quantity_received') }}:</strong> {{ $product->pivot->quantity_received ?? $product->pivot->quantity_ordered }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
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

        @if($order->status === 'waiting_for_reception')
            <a href="{{ route('supplier-orders.pdf', [$supplier, $order]) }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf-fill"></i> {{ __('messages.supplier_order.pdf') }}
            </a>
        @endif
    </div>
</div>
@endsection
