@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Refill") #{{ $refill->id }} - {{ $supplier->name }}</h1>

    <div class="mb-3">
        <strong>@t("destination_store") :</strong>
        {{ $refill->destinationStore?->name ?? '-' }}
    </div>

    <h3>@t("Products received")</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>@t("stock_value.ean")</th>
                <th>@t("Product name")</th>
                <th>@t("product.brand_label")</th>
                <th>@t("Quantity received")</th>
            </tr>
        </thead>
        <tbody>
            @foreach($refill->products as $product)
                <tr>
                    <td>{{ $product->ean }}</td>
                    <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                    <td>{{ $product->brand?->name ?? '-' }}</td>
                    <td>{{ $product->pivot->quantity_received }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('suppliers.edit', $supplier) }}#refills" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> @t("btn.back")
    </a>
</div>
@endsection
