<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('messages.stock_movement.details') }} #{{ $movement->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #eee; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .meta { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>{{ __('messages.stock_movement.details') }} #{{ $movement->id }}</h1>
    <div class="meta">
        <p><strong>{{ __('messages.stock_movement.date') }}:</strong> {{ $movement->created_at->format('d/m/Y H:i') }}</p>
        <p><strong>{{ __('messages.stock_movement.user') }}:</strong> {{ $movement->user->name }}</p>
        <p><strong>{{ __('messages.stock_movement.source') }}:</strong> {{ $movement->fromStore?->name ?? '-' }}</p>
        <p><strong>{{ __('messages.stock_movement.destination') }}:</strong> {{ $movement->toStore?->name ?? '-' }}</p>
        <p><strong>{{ __('messages.common.status') }}:</strong>
            @switch($movement->status)
                @case(\App\Models\StockMovement::STATUS_DRAFT) {{ __('messages.stock_movement.status.draft') }} @break
                @case(\App\Models\StockMovement::STATUS_VALIDATED) {{ __('messages.stock_movement.status.validated') }} @break
                @case(\App\Models\StockMovement::STATUS_IN_TRANSIT) {{ __('messages.stock_movement.status.in_transit') }} @break
                @case(\App\Models\StockMovement::STATUS_RECEIVED) {{ __('messages.stock_movement.status.received') }} @break
                @case(\App\Models\StockMovement::STATUS_CANCELLED) {{ __('messages.stock_movement.status.cancelled') }} @break
            @endswitch
        </p>
    </div>

    <h3>{{ __('messages.stock_movement.products') }}</h3>
    <table>
        <thead>
            <tr>
                <th>EAN</th>
                <th>{{ __('messages.product.name') }}</th>
                <th>{{ __('messages.stock_movement.quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement->items as $item)
            <tr>
                <td>{{ $item->product->ean }}</td>
                <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                <td>{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
