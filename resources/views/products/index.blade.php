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
                <th>{{ __('messages.product.ean') }}</th>
                <th>{{ __('messages.common.name') }}</th>
                <th style="min-width:220px;">
                    <form action="{{ route('products.index') }}" method="GET" id="brandFilterForm">
                        {{-- préserver les autres filtres/params --}}
                        @if(request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif

                        <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.all_brands') }}</option>
                            <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                                {{ __('messages.no_brand') }}
                            </option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </th>
                <th>{{ __('messages.product.price') }}</th>
                <th>{{ __('messages.product.price_btob') }}</th>
                <th>{{ __('messages.product.active_website') }}</th>
                <th>{{ __('messages.product.active_pos') }}</th>
                <th>{{ __('messages.product.best_seller') }}</th>
                <th>{{ __('messages.product.is_resalable') }}</th>
                <th class="text-center">{{ __('messages.product.shipping_weight') }}</th>
                <th class="text-center" style="width:90px;">{{ __('messages.product.photo') }}</th>
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
                <td>{{ is_array($p->name) ? ($p->name[app()->getLocale()] ?? reset($p->name)) : $p->name }}</td>

                <td>{{ $p->brand?->name ?? '-' }}</td>
                <td>{{ number_format($p->price, 2) }}</td>
                <td>
                    @if($p->price_btob !== null)
                        {{ number_format($p->price_btob, 2) }}
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    <select class="form-select form-select-sm toggle-field {{ $p->is_active ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                            data-product-id="{{ $p->id }}" data-field="is_active" style="width:70px;padding:2px 4px;font-size:0.8rem;">
                        <option value="1" {{ $p->is_active ? 'selected' : '' }}>{{ __('messages.Yes') }}</option>
                        <option value="0" {{ !$p->is_active ? 'selected' : '' }}>{{ __('messages.No') }}</option>
                    </select>
                </td>
                <td class="text-center">
                    <select class="form-select form-select-sm toggle-field {{ $p->is_active_pos ? 'bg-success-subtle' : 'bg-danger-subtle' }}"
                            data-product-id="{{ $p->id }}" data-field="is_active_pos" style="width:70px;padding:2px 4px;font-size:0.8rem;">
                        <option value="1" {{ $p->is_active_pos ? 'selected' : '' }}>{{ __('messages.Yes') }}</option>
                        <option value="0" {{ !$p->is_active_pos ? 'selected' : '' }}>{{ __('messages.No') }}</option>
                    </select>
                </td>
                <td class="text-center">
                    <select class="form-select form-select-sm toggle-field {{ $p->is_best_seller ? 'bg-success-subtle' : '' }}"
                            data-product-id="{{ $p->id }}" data-field="is_best_seller" style="width:70px;padding:2px 4px;font-size:0.8rem;">
                        <option value="1" {{ $p->is_best_seller ? 'selected' : '' }}>{{ __('messages.Yes') }}</option>
                        <option value="0" {{ !$p->is_best_seller ? 'selected' : '' }}>{{ __('messages.No') }}</option>
                    </select>
                </td>
                <td class="text-center">
                    <select class="form-select form-select-sm toggle-field {{ $p->is_resalable ? 'bg-success-subtle' : '' }}"
                            data-product-id="{{ $p->id }}" data-field="is_resalable" style="width:70px;padding:2px 4px;font-size:0.8rem;">
                        <option value="1" {{ $p->is_resalable ? 'selected' : '' }}>{{ __('messages.Yes') }}</option>
                        <option value="0" {{ !$p->is_resalable ? 'selected' : '' }}>{{ __('messages.No') }}</option>
                    </select>
                </td>
                <td class="text-center">
                    <input type="number" class="form-control form-control-sm inline-weight"
                           data-product-id="{{ $p->id }}"
                           value="{{ $p->shipping_weight }}"
                           min="0" placeholder="-"
                           style="width:80px;padding:2px 4px;font-size:0.8rem;text-align:center;">
                </td>
                <td class="text-center">
                    @if($p->images_count > 0)
                        <span class="badge bg-success">{{ $p->images_count }}</span>
                    @else
                        <span class="badge bg-secondary">0</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span>{{ __('messages.pagination.show') }}</span>
            <select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href=this.value">
                @foreach([25, 50, 100] as $option)
                    <option value="{{ request()->fullUrlWithQuery(['perPage' => $option, 'page' => 1]) }}" {{ request('perPage', 100) == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            <span>{{ __('messages.pagination.rows') }}</span>
        </div>
        <div>
            {{ $products->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl)
    })

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var baseUrl = '{{ url("products") }}';

    function inlineUpdate(productId, field, value, el, onSuccess) {
        fetch(baseUrl + '/' + productId + '/toggle-field', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ field: field, value: value })
        }).then(function(response) {
            if (response.ok) {
                if (onSuccess) onSuccess();
            } else {
                alert('Error updating field');
                location.reload();
            }
        }).catch(function() {
            alert('Error updating field');
            location.reload();
        });
    }

    document.querySelectorAll('.toggle-field').forEach(function(select) {
        select.addEventListener('change', function() {
            var el = this;
            var value = parseInt(el.value);
            var field = el.dataset.field;
            inlineUpdate(el.dataset.productId, field, value, el, function() {
                if (field === 'is_active' || field === 'is_active_pos') {
                    el.className = el.className.replace(/bg-\w+-subtle/g, '');
                    el.classList.add(value ? 'bg-success-subtle' : 'bg-danger-subtle');
                } else {
                    el.className = el.className.replace(/bg-\w+-subtle/g, '');
                    if (value) el.classList.add('bg-success-subtle');
                }
            });
        });
    });

    document.querySelectorAll('.inline-weight').forEach(function(input) {
        var original = input.value;
        input.addEventListener('change', function() {
            var el = this;
            var value = el.value !== '' ? parseInt(el.value) : '';
            if (String(value) === original) return;
            inlineUpdate(el.dataset.productId, 'shipping_weight', value, el, function() {
                original = String(value);
                el.classList.add('bg-success-subtle');
                setTimeout(function() { el.classList.remove('bg-success-subtle'); }, 1000);
            });
        });
    });
});
</script>
@endpush
@endsection
