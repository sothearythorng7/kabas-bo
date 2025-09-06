<h5>{{ __('messages.product.stores') }}</h5>
<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>{{ __('messages.store.name') }}</th>
            <th style="width: 150px;">{{ __('messages.store.stock_quantity') }}</th>
            <th style="width: 150px;">{{ __('messages.store.stock_alert') }}</th>
            <th style="width: 120px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($product->stores as $store)
            <tr>
                <td>{{ $store->name }}</td>
                <form action="{{ route('products.stores.updateStock', [$product, $store]) }}" method="POST" class="d-flex">
                    @csrf
                    @method('PUT')
                    <td><input type="number" min="0" name="stock_quantity" class="form-control form-control-sm" value="{{ $store->pivot->stock_quantity }}"></td>
                    <td><input type="number" min="0" name="alert_stock_quantity" class="form-control form-control-sm" placeholder="Alert" value="{{ $store->pivot->alert_stock_quantity }}"></td>
                    <td><button class="btn btn-sm btn-success ms-2"><i class="bi bi-check"></i></button></td>
                </form>
            </tr>
        @empty
            <tr><td colspan="4" class="text-muted">{{ __('messages.product.no_store') }}</td></tr>
        @endforelse
    </tbody>
</table>
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStoreModal">
    <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_store') }}
</button>
@include('products.partials.modal-add-store')
