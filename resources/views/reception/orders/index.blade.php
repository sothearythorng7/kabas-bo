@extends('reception.layouts.app')

@section('title', 'Supplier Orders')

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
    <div class="header-title">Supplier Orders</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(request('saved'))
        <div class="alert alert-success">Quantities saved. You can continue this reception later.</div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    @if($orders->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No orders waiting</div>
            <div>All supplier orders have been received</div>
        </div>
    @else
        @foreach($orders as $order)
            @php
                $hasPartialReception = in_array($order->id, $ordersWithPartialReception ?? []);
            @endphp
            <div class="card" style="cursor: pointer;{{ $hasPartialReception ? ' border-left: 4px solid var(--warning);' : '' }}" onclick="window.location='{{ route('reception.orders.show', $order) }}'">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="card-title">
                            {{ $order->supplier->name }}
                            @if($hasPartialReception)
                                <span class="badge" style="background: var(--warning); color: #000; font-size: 10px; margin-left: 8px;">In Progress</span>
                            @endif
                        </div>
                        <div class="card-subtitle">
                            {{ $order->created_at->format('d/m/Y') }}
                            &bull;
                            {{ $order->products->count() }} products
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
