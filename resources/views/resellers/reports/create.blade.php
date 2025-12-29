@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.new_sales_report') }} {{ $reseller->name }}</h1>

    <form action="{{ route('resellers.reports.store', $reseller->id) }}" method="POST">
        @csrf

        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('messages.product.name') }}</th>
                    <th>{{ __('messages.stock_movement.quantity') }}</th>
                    <th>{{ __('messages.resellers.quantity_sold') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                        <td>{{ $stock[$product->id] ?? 0 }}</td>
                        <td>
                            <input type="number" name="products[{{ $loop->index }}][quantity]"
                                   value="0" min="0" class="form-control" style="width:120px;">
                            <input type="hidden" name="products[{{ $loop->index }}][id]" value="{{ $product->id }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </form>
</div>
@endsection
