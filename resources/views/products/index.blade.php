@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.title') }}</h1>

    <a href="{{ route('products.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.product.btnCreate') }}
    </a>
    <div class="mb-3">
        <form action="{{ route('products.index') }}" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" 
                    placeholder="{{ __('messages.searchBy') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> {{ __('messages.search') }}
                </button>
            </div>
            @if(request('q'))
            <div class="col-md-2">
                <a href="{{ route('products.index') }}" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> {{ __('messages.reset') }}
                </a>
            </div>
            @endif
        </form>
    </div>
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th></th> <!-- Warning column -->
                    <th>EAN</th>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>Brand</th>
                    <th>Price</th>
                    <th>Active</th>
                    <th>Best</th>
                    <th>Resalable</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                    @foreach($products as $p)
                    @php
                        $lowStockStores = [];
                        foreach($p->stores as $store) {
                            if($store->pivot->stock_quantity <= $store->pivot->alert_stock_quantity) {
                                $lowStockStores[] = $store->name . ', ' . __('messages.store.stocklow') . ': ' . $store->pivot->stock_quantity;
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            @if(count($lowStockStores))
                                <i class="bi bi-exclamation-triangle-fill text-warning" 
                                data-bs-toggle="tooltip" 
                                data-bs-placement="top" 
                                title="{{ implode("\n", $lowStockStores) }}"></i>
                            @endif
                        </td>
                        <td>{{ $p->ean }}</td>
                        <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>
                        <td>{{ $p->brand?->name ?? '-' }}</td>
                        <td>{{ number_format($p->price, 2) }}</td>
                        <td style="text-center">{{ $p->is_active ? 'Yes' : 'No' }}</td>
                        <td style="text-center">{{ $p->is_best_seller ? 'Yes' : 'No' }}</td>
                        <td style="text-center">{{ $p->is_resalable ? 'Yes' : 'No' }}</td>
                        <td class="d-flex justify-content-end gap-1">
                            <a href="{{ route('products.edit', $p) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                            </a>
                            <form action="{{ route('products.destroy', $p) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('{{ __('messages.product.confirm_delete') }}')">
                                    <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach

            </tbody>
        </table>

        {{ $products->links() }}
    </div>

    <div class="d-md-none">
        <div class="row">
            @foreach($products as $p)
            @php
                $lowStockStores = [];
                foreach($p->stores as $store) {
                    if($store->pivot->stock_quantity <= $store->pivot->alert_stock_quantity) {
                        $lowStockStores[] = $store->name . ', stock bas: ' . $store->pivot->stock_quantity;
                    }
                }
            @endphp
            <div class="col-12 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-1">
                            @if(count($lowStockStores))
                                <i class="bi bi-exclamation-triangle-fill text-warning" 
                                   data-bs-toggle="tooltip" 
                                   data-bs-placement="top" 
                                   title="{{ implode("\n", $lowStockStores) }}"></i>
                            @endif
                            {{ $p->name[app()->getLocale()] ?? reset($p->name) }}
                        </h5>
                        <p class="mb-1"><strong>EAN:</strong> {{ $p->ean }}</p>
                        <p class="mb-1"><strong>Brand:</strong> {{ $p->brand?->name ?? '-' }}</p>
                        <p class="mb-1"><strong>Price:</strong> {{ number_format($p->price, 2) }}</p>
                        <p class="mb-1"><strong>Active:</strong> {{ $p->is_active ? 'Yes' : 'No' }}</p>
                        <p class="mb-1"><strong>Best:</strong> {{ $p->is_best_seller ? 'Yes' : 'No' }}</p>
                        <p class="mb-1"><strong>Resalable:</strong> {{ $p->is_resalable ? 'Yes' : 'No' }}</p>
                        <div class="d-flex justify-content-between mt-2">
                            <a href="{{ route('products.edit', $p) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('products.destroy', $p) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('{{ __('messages.product.confirm_delete') }}')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            {{ $products->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endpush
@endsection
