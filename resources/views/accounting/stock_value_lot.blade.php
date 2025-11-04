<h5>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>

@if($lots->isEmpty())
    <p>{{ __('messages.stock_value.no_lots') }}</p>
@else
<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>{{ __('messages.stock_value.store') }}</th>
            <th>{{ __('messages.stock_value.remaining_stock') }}</th>
            <th>{{ __('messages.stock_value.purchase_price') }}</th>
            <th>{{ __('messages.stock_value.estimated_value') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lots as $lot)
        <tr>
            <td>{{ $lot->store->name ?? '-' }}</td>
            <td>{{ $lot->quantity_remaining }}</td>
            <td>{{ number_format($lot->purchase_price, 2) }} $</td>
            <td>{{ number_format($lot->quantity_remaining * $lot->purchase_price, 2) }} $</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
