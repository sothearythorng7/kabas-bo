<h5>{{ __('messages.product.suppliers') }}</h5>
<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>{{ __('messages.supplier.name') }}</th>
            <th style="width: 150px;">{{ __('messages.supplier.purchase_price') }}</th>
            <th style="width: 120px;"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($product->suppliers as $supplier)
            <tr>
                <td>{{ $supplier->name }}</td>
                <td>
                    <form action="{{ route('products.suppliers.updatePrice', [$product, $supplier]) }}" method="POST" class="d-flex">
                        @csrf
                        @method('PUT')
                        <input type="number" step="0.01" name="purchase_price" class="form-control form-control-sm" value="{{ $supplier->pivot->purchase_price }}">
                        <button class="btn btn-sm btn-success ms-2"><i class="bi bi-check"></i></button>
                    </form>
                </td>
                <td>
                    <form action="{{ route('products.suppliers.detach', [$product, $supplier]) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-muted">{{ __('messages.product.no_supplier') }}</td></tr>
        @endforelse
    </tbody>
</table>
<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
    <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_supplier') }}
</button>
@include('products.partials.modal-add-supplier')
