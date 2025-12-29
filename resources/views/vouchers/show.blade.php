@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.voucher.details') }}: {{ $voucher->code }}</h1>
        <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.voucher.info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">{{ __('messages.voucher.code') }}</th>
                            <td><code class="fs-4">{{ $voucher->code }}</code></td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.amount') }}</th>
                            <td class="fs-4 fw-bold text-success">{{ number_format($voucher->amount, 2) }} $</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.status') }}</th>
                            <td>
                                @switch($voucher->status)
                                    @case('active')
                                        <span class="badge bg-success fs-6">{{ __('messages.voucher.statuses.active') }}</span>
                                        @break
                                    @case('used')
                                        <span class="badge bg-secondary fs-6">{{ __('messages.voucher.statuses.used') }}</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-warning text-dark fs-6">{{ __('messages.voucher.statuses.expired') }}</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-danger fs-6">{{ __('messages.voucher.statuses.cancelled') }}</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.source') }}</th>
                            <td>
                                @switch($voucher->source_type)
                                    @case('exchange')
                                        <span class="badge bg-info">{{ __('messages.voucher.sources.exchange') }}</span>
                                        @if($voucher->sourceExchange)
                                            <a href="{{ route('exchanges.show', $voucher->sourceExchange) }}" class="ms-2">
                                                {{ __('messages.exchange.view') }} #{{ $voucher->source_exchange_id }}
                                            </a>
                                        @endif
                                        @break
                                    @case('manual')
                                        <span class="badge bg-primary">{{ __('messages.voucher.sources.manual') }}</span>
                                        @break
                                    @case('promotion')
                                        <span class="badge bg-purple">{{ __('messages.voucher.sources.promotion') }}</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.expires_at') }}</th>
                            <td>
                                {{ $voucher->expires_at->format('d/m/Y') }}
                                @if($voucher->expires_at->isPast())
                                    <span class="text-danger">({{ __('messages.voucher.expired') }})</span>
                                @else
                                    <span class="text-muted">({{ $voucher->expires_at->diffForHumans() }})</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($voucher->status === 'active')
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">{{ __('messages.voucher.cancel') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('vouchers.cancel', $voucher) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.voucher.cancellation_reason') }}</label>
                            <textarea name="reason" class="form-control" required rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('{{ __('messages.voucher.confirm_cancel') }}')">
                            <i class="bi bi-x-circle"></i> {{ __('messages.voucher.cancel') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.voucher.creation_info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">{{ __('messages.voucher.created_at') }}</th>
                            <td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.created_by') }}</th>
                            <td>{{ $voucher->createdByUser?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.store.name') }}</th>
                            <td>{{ $voucher->createdAtStore?->name ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($voucher->status === 'used')
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">{{ __('messages.voucher.usage_info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">{{ __('messages.voucher.used_at') }}</th>
                            <td>{{ $voucher->used_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.used_store') }}</th>
                            <td>{{ $voucher->usedAtStore?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.used_sale') }}</th>
                            <td>
                                @if($voucher->usedInSale)
                                    {{ __('messages.sale.id') }} #{{ $voucher->used_in_sale_id }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            @if($voucher->status === 'cancelled')
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">{{ __('messages.voucher.cancellation_info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">{{ __('messages.voucher.cancelled_at') }}</th>
                            <td>{{ $voucher->cancelled_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.cancelled_by') }}</th>
                            <td>{{ $voucher->cancelledByUser?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.voucher.cancellation_reason') }}</th>
                            <td>{{ $voucher->cancellation_reason ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
