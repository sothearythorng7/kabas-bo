@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">
            {{ $popupEvent->name }}
            <small class="text-muted">({{ $popupEvent->reference }})</small>
            @switch($popupEvent->status)
                @case('planned')
                    <span class="badge bg-secondary">{{ __('messages.popup_event.status_planned') }}</span>
                    @break
                @case('active')
                    <span class="badge bg-success">{{ __('messages.popup_event.status_active') }}</span>
                    @break
                @case('completed')
                    <span class="badge bg-primary">{{ __('messages.popup_event.status_completed') }}</span>
                    @break
                @case('cancelled')
                    <span class="badge bg-danger">{{ __('messages.popup_event.status_cancelled') }}</span>
                    @break
            @endswitch
        </h1>
        <a href="{{ route('popup-events.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Info Cards --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.popup_event.details') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.popup_event.reference') }}:</strong> {{ $popupEvent->reference }}</p>
                    <p><strong>{{ __('messages.popup_event.store') }}:</strong> {{ $popupEvent->store->name }}</p>
                    @if($popupEvent->location)
                        <p><strong>{{ __('messages.popup_event.location') }}:</strong> {{ $popupEvent->location }}</p>
                    @endif
                    <p><strong>{{ __('messages.popup_event.dates') }}:</strong>
                        {{ $popupEvent->start_date->format('d/m/Y') }}
                        @if($popupEvent->end_date) - {{ $popupEvent->end_date->format('d/m/Y') }} @endif
                    </p>
                    @if($popupEvent->notes)
                        <p><strong>{{ __('messages.popup_event.notes') }}:</strong> {{ $popupEvent->notes }}</p>
                    @endif
                    <p><strong>{{ __('messages.popup_event.created_by') }}:</strong> {{ $popupEvent->createdBy?->name ?? '-' }}</p>
                    <p><strong>{{ __('messages.common.date') }}:</strong> {{ $popupEvent->created_at->format('d/m/Y H:i') }}</p>
                    @if($popupEvent->activated_at)
                        <p><strong>{{ __('messages.popup_event.activated_at') }}:</strong> {{ $popupEvent->activated_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($popupEvent->completed_at)
                        <p><strong>{{ __('messages.popup_event.completed_at') }}:</strong> {{ $popupEvent->completed_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($stats)
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.popup_event.kpi') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3 class="mb-0">{{ $stats['total_sales'] }}</h3>
                            <small class="text-muted">{{ __('messages.popup_event.total_sales') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h3 class="mb-0">${{ number_format($stats['total_revenue'], 2) }}</h3>
                            <small class="text-muted">{{ __('messages.popup_event.total_revenue') }}</small>
                        </div>
                        <div class="col-md-4">
                            <h3 class="mb-0">${{ number_format($stats['avg_basket'], 2) }}</h3>
                            <small class="text-muted">{{ __('messages.popup_event.avg_basket') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.popup_event.summary') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.popup_event.total_products') }}:</strong> {{ $popupEvent->items->count() }}</p>
                    <p><strong>{{ __('messages.popup_event.total_allocated') }}:</strong> {{ $popupEvent->total_allocated }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Products Table --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('messages.popup_event.products') }}</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th class="text-center">{{ __('messages.popup_event.quantity_allocated') }}</th>
                        @if($popupEvent->isCompleted() || $popupEvent->isActive())
                            <th class="text-center">{{ __('messages.popup_event.quantity_sold') }}</th>
                            <th class="text-center">{{ __('messages.popup_event.quantity_remaining') }}</th>
                            <th class="text-center">{{ __('messages.popup_event.sell_through') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($popupEvent->items as $item)
                        <tr>
                            <td>{{ $item->product->ean ?? '-' }}</td>
                            <td>{{ is_array($item->product->name) ? ($item->product->name[app()->getLocale()] ?? reset($item->product->name)) : $item->product->name }}</td>
                            <td>{{ $item->product->brand?->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity_allocated }}</td>
                            @if($popupEvent->isCompleted() || $popupEvent->isActive())
                                <td class="text-center">{{ $item->quantity_sold }}</td>
                                <td class="text-center">{{ $item->quantity_remaining }}</td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $item->sell_through_rate >= 75 ? 'bg-success' : ($item->sell_through_rate >= 50 ? 'bg-info' : 'bg-warning') }}"
                                             style="width: {{ $item->sell_through_rate }}%">
                                            {{ $item->sell_through_rate }}%
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="3">{{ __('messages.popup_event.total') }}</th>
                        <th class="text-center">{{ $popupEvent->total_allocated }}</th>
                        @if($popupEvent->isCompleted() || $popupEvent->isActive())
                            <th class="text-center">{{ $popupEvent->total_sold }}</th>
                            <th class="text-center">{{ $popupEvent->total_allocated - $popupEvent->total_sold }}</th>
                            <th class="text-center">
                                @if($popupEvent->total_allocated > 0)
                                    {{ round(($popupEvent->total_sold / $popupEvent->total_allocated) * 100, 1) }}%
                                @endif
                            </th>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Shifts --}}
    @if(($popupEvent->isActive() || $popupEvent->isCompleted()) && $popupEvent->shifts->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('messages.popup_event.shifts') }}</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.popup_event.cashier') }}</th>
                        <th>{{ __('messages.popup_event.started') }}</th>
                        <th>{{ __('messages.popup_event.ended') }}</th>
                        <th class="text-center">{{ __('messages.popup_event.nb_sales') }}</th>
                        <th class="text-end">{{ __('messages.popup_event.total_revenue') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($popupEvent->shifts as $shift)
                        <tr>
                            <td>{{ $shift->user?->name ?? '-' }}</td>
                            <td>{{ $shift->started_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $shift->ended_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-center">{{ $shift->sales->count() }}</td>
                            <td class="text-end">${{ number_format($shift->sales->sum('total'), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="d-flex gap-2 flex-wrap">
        @if($popupEvent->isPlanned())
            <a href="{{ route('popup-events.edit', $popupEvent) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
            </a>

            <form action="{{ route('popup-events.activate', $popupEvent) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.popup_event.confirm_activate') }}')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-play-circle"></i> {{ __('messages.popup_event.activate') }}
                </button>
            </form>

            <form action="{{ route('popup-events.destroy', $popupEvent) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.popup_event.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                </button>
            </form>
        @endif

        @if($popupEvent->isActive())
            <form action="{{ route('popup-events.complete', $popupEvent) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.popup_event.confirm_complete') }}')">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> {{ __('messages.popup_event.complete') }}
                </button>
            </form>

            <form action="{{ route('popup-events.cancel', $popupEvent) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.popup_event.confirm_cancel') }}')">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> {{ __('messages.popup_event.cancel_event') }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection
