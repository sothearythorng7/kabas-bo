@extends('reception.layouts.app')

@section('title', __('messages.reception.transfer') . ' #' . $movement->id)

@section('content')
<div class="header">
    <a href="{{ route('reception.transfers') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ __('messages.reception.transfer') }} #{{ $movement->id }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container has-sticky-bottom">
    <!-- Transfer info card -->
    <div class="card" style="margin-bottom: 16px;">
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <div class="flex justify-between">
                <span class="text-muted">{{ __('messages.reception.from') }}:</span>
                <span class="font-bold">{{ $movement->fromStore->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">{{ __('messages.reception.to') }}:</span>
                <span class="font-bold">{{ $movement->toStore->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">{{ __('messages.reception.created_by') }}:</span>
                <span>{{ $movement->user->name ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-muted">{{ __('messages.reception.date') }}:</span>
                <span>{{ $movement->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if($movement->note)
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border);">
                <span class="text-muted">{{ __('messages.reception.note') }}:</span>
                <div style="margin-top: 4px;">{{ $movement->note }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Products list -->
    <div class="text-sm text-muted mb-4">{{ __('messages.reception.products_to_receive') }}</div>

    @foreach($movement->items as $item)
        @php
            $product = $item->product;
            $productName = $product->name[app()->getLocale()] ?? $product->name['en'] ?? reset($product->name);
            $thumbnail = $product->images->first() ? asset('storage/' . $product->images->first()->path) : asset('images/placeholder.png');
        @endphp
        <div class="product-item">
            <img src="{{ $thumbnail }}" alt="" class="product-image">
            <div class="product-info">
                <div class="product-name">{{ $productName }}</div>
                <div class="product-meta">EAN: {{ $product->ean }}</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 20px; font-weight: 600;">{{ $item->quantity }}</div>
                <div class="text-sm text-muted">{{ __('messages.reception.units') }}</div>
            </div>
        </div>
    @endforeach

    <!-- Total -->
    <div class="card" style="margin-top: 16px; background: var(--primary); color: white;">
        <div class="flex justify-between items-center">
            <span>{{ __('messages.reception.total_products') }}</span>
            <span style="font-size: 24px; font-weight: 600;">{{ $movement->items->sum('quantity') }}</span>
        </div>
    </div>
</div>

<div class="sticky-bottom">
    <form action="{{ route('reception.transfers.receive', $movement) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-success" onclick="return confirm('{{ __('messages.reception.confirm_receive') }}')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ __('messages.reception.confirm_reception') }}
        </button>
    </form>
</div>
@endsection
