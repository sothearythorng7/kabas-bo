@extends('reception.layouts.app')

@section('title', __('messages.reception.home'))

@section('content')
<div class="header">
    <div class="header-title">{{ __('messages.reception.title') }}</div>
    <form action="{{ route('reception.logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" style="background: none; border: none; color: white; font-size: 14px; cursor: pointer;">
            {{ __('messages.reception.logout') }}
        </button>
    </form>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="welcome-card card">
        <div class="text-center">
            <div style="font-size: 14px; color: var(--text-light);">{{ __('messages.reception.welcome_back') }}</div>
            <div style="font-size: 20px; font-weight: 600; margin-top: 4px;">{{ $userName }}</div>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 16px; margin-top: 24px;">
        <a href="{{ route('reception.orders') }}" class="btn btn-primary btn-lg">
            <span class="btn-icon">📦</span>
            <span>{{ __('messages.reception.supplier_orders') }}</span>
            @if($pendingOrdersCount > 0)
                <span class="badge badge-warning">{{ $pendingOrdersCount }}</span>
            @endif
        </a>

        <a href="{{ route('reception.transfers') }}" class="btn btn-primary btn-lg">
            <span class="btn-icon">🚚</span>
            <span>{{ __('messages.reception.stock_transfers') }}</span>
            @if($pendingTransfersCount > 0)
                <span class="badge badge-warning">{{ $pendingTransfersCount }}</span>
            @endif
        </a>

        <a href="{{ route('reception.refill') }}" class="btn btn-outline btn-lg">
            <span class="btn-icon">🔄</span>
            <span>{{ __('messages.reception.refill') }}</span>
        </a>

        <a href="{{ route('reception.returns') }}" class="btn btn-outline btn-lg">
            <span class="btn-icon">↩️</span>
            <span>{{ __('messages.reception.return') }}</span>
        </a>

        <a href="{{ route('reception.check-price') }}" class="btn btn-secondary btn-lg">
            <span class="btn-icon">📷</span>
            <span>{{ __('messages.reception.check_price') }}</span>
        </a>
    </div>
</div>
@endsection
