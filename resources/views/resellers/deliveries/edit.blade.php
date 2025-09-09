@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Edit Reseller Delivery #{{ $delivery->id }}</h1>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="deliveryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                General Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                Products
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="deliveryTabsContent">

        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <form action="{{ route('reseller-stock-deliveries.update', [$reseller, $delivery]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        @foreach(\App\Models\ResellerStockDelivery::STATUS_OPTIONS as $key => $label)
                            <option value="{{ $key }}" @selected($delivery->status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="shipping_cost" class="form-label">Shipping Cost (€)</label>
                    <input type="number" step="0.01" name="shipping_cost" id="shipping_cost" class="form-control" value="{{ old('shipping_cost', $delivery->shipping_cost) }}">
                    <small class="text-muted">Only editable after creation</small>
                </div>

                <button type="submit" class="btn btn-success">Save</button>
                <a href="{{ route('resellers.show', $reseller) }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            <h3>Products in this Delivery</h3>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Quantity</th>
                        <th>Unit Price (€)</th>
                        <th>Total (€)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($delivery->products as $product)
                        <tr>
                            <td>{{ $product->ean }}</td>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>{{ $product->brand?->name ?? '-' }}</td>
                            <td>{{ $product->pivot->quantity }}</td>
                            <td>{{ number_format($product->pivot->unit_price, 2) }}</td>
                            <td>{{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
