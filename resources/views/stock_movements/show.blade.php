@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        {{ __('messages.stock_movement.details') }} - {{ $movement->created_at->format('d/m/Y H:i') }}
    </h1>

    <a href="{{ route('stock-movements.pdf', $movement) }}" class="btn btn-primary mt-3">
        <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.btn.export_pdf') }}
    </a>
    

    <p><strong>{{ __('messages.stock_movement.user') }}:</strong> {{ $movement->user->name }}</p>
    <p><strong>{{ __('messages.stock_movement.source') }}:</strong> {{ $movement->fromStore?->name ?? '-' }}</p>
    <p><strong>{{ __('messages.stock_movement.destination') }}:</strong> {{ $movement->toStore?->name ?? '-' }}</p>
    <p><strong>Status:</strong>
        @switch($movement->status)
            @case(\App\Models\StockMovement::STATUS_DRAFT)
                <span class="badge bg-secondary">{{ __('messages.stock_movement.status.draft')}}</span>
                @break
            @case(\App\Models\StockMovement::STATUS_VALIDATED)
                <span class="badge bg-primary">{{ __('messages.stock_movement.status.validated')}}</span>
                @break
            @case(\App\Models\StockMovement::STATUS_IN_TRANSIT)
                <span class="badge bg-warning text-dark">{{ __('messages.stock_movement.status.in_transit')}}</span>
                @break
            @case(\App\Models\StockMovement::STATUS_RECEIVED)
                <span class="badge bg-success">{{ __('messages.stock_movement.status.received')}}</span>
                @break
            @case(\App\Models\StockMovement::STATUS_CANCELLED)
                <span class="badge bg-danger">{{ __('messages.stock_movement.status.cancelled')}}</span>
                @break
        @endswitch
    </p>

    <h4 class="mt-4">{{ __('messages.stock_movement.products') }}</h4>
    <table class="table table-bordered">
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

    <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left-circle"></i> {{ __('messages.btn.back') }}
    </a>
</div>
@endsection
