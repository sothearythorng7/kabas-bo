@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier_order.create_title') }} - {{ $supplier->name }}</h1>

    {{-- Version desktop --}}
    <div class="d-none d-md-block">
        <form action="{{ route('supplier-orders.store', $supplier) }}" method="POST">
            @csrf

            <!-- Menu déroulant pour la destination -->
            <div class="mb-3">
                <label for="destination_store_id" class="form-label">{{ __('messages.supplier_order.destination_store') }}</label>
                <select name="destination_store_id" id="destination_store_id" class="form-control" required>
                    <option value="">{{ __('messages.supplier_order.select_destination') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->type }})</option>
                    @endforeach
                </select>
                @error('destination_store_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>Brand</th>
                        <th>{{ __('messages.product.purchase_price') }}</th>
                        <th>{{ __('messages.product.price') }}</th>
                        <th>{{ __('messages.supplier_order.quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->ean }}</td>
                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>
                        <td>{{ number_format($product->pivot->purchase_price ?? 0, 2) }}</td>
                        <td>{{ number_format($product->price, 2) }}</td>
                        <td>
                            <input type="number" min="0" name="products[{{ $product->id }}][quantity]" value="0" class="form-control form-control-sm" style="max-width:100px;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier_order.create') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Version mobile --}}
    <div class="d-md-none">
        <form action="{{ route('supplier-orders.store', $supplier) }}" method="POST">
            @csrf

            <!-- Menu déroulant pour la destination -->
            <div class="mb-3">
                <label for="destination_store_id" class="form-label">{{ __('messages.supplier_order.destination_store') }}</label>
                <select name="destination_store_id" id="destination_store_id" class="form-control" required>
                    <option value="">{{ __('messages.supplier_order.select_destination') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }} ({{ $store->type }})</option>
                    @endforeach
                </select>
                @error('destination_store_id')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                @foreach($products as $product)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                            <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                            <p class="mb-1"><strong>Brand:</strong> {{ $product->brand?->name ?? '-' }}</p>
                            <p class="mb-1"><strong>{{ __('messages.product.purchase_price') }}:</strong> {{ number_format($product->pivot->purchase_price ?? 0, 2) }}</p>
                            <p class="mb-1"><strong>{{ __('messages.product.price') }}:</strong> {{ number_format($product->price, 2) }}</p>
                            <div class="mb-1">
                                <label class="form-label">{{ __('messages.supplier_order.quantity') }}</label>
                                <input type="number" min="0" name="products[{{ $product->id }}][quantity]" value="0" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier_order.create') }}
                </button>
            </div>
        </form>
    </div>

    <div class="mt-3">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
        </a>
    </div>
</div>
@endsection