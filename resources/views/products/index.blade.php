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

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th>@t("product.ean")</th>
                <th>@t("Product name")</th>
                <th>@t("product.brand_label")</th>
                <th>@t("product.price")</th>
                <th>@t("product.price_btob")</th> <!-- Nouvelle colonne -->
                <th>@t("product.active")</th>
                <th>@t("Best")</th>
                <th>@t("Resalable")</th>
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
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow " type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start">
                            <li>
                                <a href="{{ route('products.edit', $p) }}" class="dropdown-item">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('products.destroy', $p) }}" method="POST" onsubmit="return confirm('{{ __('messages.product.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
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
                <td>
                    @if($p->price_btob !== null)
                        {{ number_format($p->price_btob, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-center">{{ $p->is_active ? 'Yes' : 'No' }}</td>
                <td style="text-center">{{ $p->is_best_seller ? 'Yes' : 'No' }}</td>
                <td style="text-center">{{ $p->is_resalable ? 'Yes' : 'No' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $products->links() }}
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
