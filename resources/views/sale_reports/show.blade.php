@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('Sale Report') }} - {{ $saleReport->store->name }}</h1>
    <p>{{ __('Period') }}: {{ $saleReport->period_start->format('d/m/Y') }} - {{ $saleReport->period_end->format('d/m/Y') }}</p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Quantity Sold') }}</th>
                <th>{{ __('Unit Price') }}</th>
                <th>{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReport->items as $item)
                <tr>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->quantity_sold }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
