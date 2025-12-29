@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        {{ __('messages.stock_movement.details') }} - {{ $movement->created_at->format('d/m/Y H:i') }}
    </h1>

    <div class="d-flex gap-2 mt-3 mb-3">
        <a href="{{ route('stock-movements.pdf', $movement) }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.btn.export_pdf') }}
        </a>
        @if($movement->invoice_path)
        <a href="{{ route('stock-movements.invoice', $movement) }}" class="btn btn-success">
            <i class="bi bi-receipt"></i> {{ __('messages.stock_movement.download_invoice') }}
        </a>
        @endif
    </div>

    <div class="row">
        <div class="col-md-6">
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
        </div>

        @if($movement->invoice_number)
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-receipt"></i> {{ __('messages.stock_movement.invoice_info') }}</h5>
                    <p class="mb-1"><strong>{{ __('messages.stock_movement.invoice_number') }}:</strong> {{ $movement->invoice_number }}</p>
                    <p class="mb-1"><strong>{{ __('messages.stock_movement.total_amount') }}:</strong> {{ number_format($movement->total_amount, 2) }} USD</p>
                    @if($movement->fromTransaction)
                    <p class="mb-1">
                        <strong>{{ __('messages.stock_movement.transaction') }} {{ $movement->fromStore->name }}:</strong>
                        <span class="badge bg-success">+{{ number_format($movement->fromTransaction->amount, 2) }} USD ({{ __('messages.stock_movement.credit') }})</span>
                    </p>
                    @endif
                    @if($movement->toTransaction)
                    <p class="mb-0">
                        <strong>{{ __('messages.stock_movement.transaction') }} {{ $movement->toStore->name }}:</strong>
                        <span class="badge bg-danger">-{{ number_format($movement->toTransaction->amount, 2) }} USD ({{ __('messages.stock_movement.debit') }})</span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <h4 class="mt-4">{{ __('messages.stock_movement.products') }}</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>EAN</th>
                <th>{{ __('messages.product.name') }}</th>
                <th class="text-center">{{ __('messages.stock_movement.quantity') }}</th>
                <th class="text-end">{{ __('messages.stock_movement.unit_price') }}</th>
                <th class="text-end">{{ __('messages.stock_movement.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($movement->items as $item)
            @php
                $lineTotal = $item->quantity * ($item->unit_price ?? 0);
                $grandTotal += $lineTotal;
            @endphp
            <tr>
                <td>{{ $item->product->ean }}</td>
                <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">{{ number_format($item->unit_price ?? 0, 2) }} $</td>
                <td class="text-end">{{ number_format($lineTotal, 2) }} $</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="table-dark">
                <td colspan="4" class="text-end"><strong>{{ __('messages.stock_movement.total') }}</strong></td>
                <td class="text-end"><strong>{{ number_format($grandTotal, 2) }} USD</strong></td>
            </tr>
        </tfoot>
    </table>

    <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary mt-3">
        <i class="bi bi-arrow-left-circle"></i> {{ __('messages.btn.back') }}
    </a>
</div>
@endsection
