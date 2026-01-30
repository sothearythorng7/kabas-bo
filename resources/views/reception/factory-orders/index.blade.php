@extends('reception.layouts.app')

@section('title', __('messages.reception.factory_orders'))

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ __('messages.reception.factory_orders') }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(request('saved'))
        <div class="alert alert-success">{{ __('messages.reception.quantities_saved') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @if($orders->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">🏭</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">{{ __('messages.reception.no_factory_orders') }}</div>
            <div>{{ __('messages.reception.all_factory_orders_received') }}</div>
        </div>
    @else
        @foreach($orders as $order)
            @php
                $hasPartialReception = in_array($order->id, $ordersWithPartialReception ?? []);
            @endphp
            <div class="card" style="cursor: pointer;{{ $hasPartialReception ? ' border-left: 4px solid var(--warning);' : '' }}" onclick="window.location='{{ route('reception.factory-orders.show', $order) }}'">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="card-title">
                            {{ $order->supplier->name }}
                            @if($hasPartialReception)
                                <span class="badge" style="background: var(--warning); color: #000; font-size: 10px; margin-left: 8px;">{{ __('messages.reception.in_progress') }}</span>
                            @endif
                        </div>
                        <div class="card-subtitle">
                            {{ $order->created_at->format('d/m/Y') }}
                            &bull;
                            {{ $order->rawMaterials->count() }} {{ __('messages.reception.raw_materials') }}
                        </div>
                        @if($order->destinationStore)
                            <div class="card-subtitle" style="margin-top: 4px;">
                                <span class="badge badge-primary" style="font-size: 11px;">
                                    {{ $order->destinationStore->name }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px; color: var(--text-light);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
