@extends('reception.layouts.app')

@section('title', __('messages.reception.stock_transfers'))

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ __('messages.reception.stock_transfers') }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <!-- Create new transfer button -->
    <a href="{{ route('reception.transfers.create') }}" class="btn btn-success mb-4" style="margin-bottom: 24px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        {{ __('messages.reception.create_transfer') }}
    </a>

    <!-- Pending transfers to receive -->
    <div class="text-sm text-muted mb-4" style="margin-top: 16px;">
        {{ __('messages.reception.pending_transfers') }}
    </div>

    @if($pendingTransfers->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">{{ __('messages.reception.no_pending_transfers') }}</div>
            <div>{{ __('messages.reception.no_pending_transfers_desc') }}</div>
        </div>
    @else
        @foreach($pendingTransfers as $transfer)
            <div class="card" style="cursor: pointer;" onclick="window.location='{{ route('reception.transfers.show', $transfer) }}'">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="card-title">
                            {{ __('messages.reception.transfer') }} #{{ $transfer->id }}
                        </div>
                        <div class="card-subtitle">
                            {{ __('messages.reception.from') }}: {{ $transfer->fromStore->name }}
                        </div>
                        <div class="card-subtitle">
                            {{ __('messages.reception.to') }}: {{ $transfer->toStore->name }}
                        </div>
                        <div class="card-subtitle">
                            {{ $transfer->items->count() }} {{ __('messages.reception.products') }} - {{ $transfer->created_at->format('d/m/Y H:i') }}
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($transfer->status === \App\Models\StockMovement::STATUS_VALIDATED)
                            <span class="badge badge-primary">{{ __('messages.stock_movement.status.validated') }}</span>
                        @else
                            <span class="badge badge-warning">{{ __('messages.stock_movement.status.in_transit') }}</span>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px; color: var(--text-light);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
