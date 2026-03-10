@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">{{ __('messages.special_order.edit_title') }} {{ $order->order_number }}</h1>
        <a href="{{ route('special-orders.show', $order) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.special_order.back_to_detail') }}
        </a>
    </div>

    <form action="{{ route('special-orders.update', $order) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- Status & Tracking -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-arrow-repeat"></i> {{ __('messages.website_order.update_status') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.website_order.status') }}</label>
                                <select name="status" class="form-select">
                                    @foreach(\App\Models\WebsiteOrder::statuses() as $status)
                                        <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                            {{ __('messages.website_order.status_' . $status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><i class="bi bi-truck"></i> {{ __('messages.website_order.tracking_url') }}</label>
                                <input type="url" name="tracking_url" class="form-control" value="{{ $order->tracking_url }}"
                                       placeholder="{{ __('messages.website_order.tracking_url_placeholder') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deposit -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-piggy-bank"></i> {{ __('messages.special_order.deposit') }}
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('messages.special_order.deposit_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="deposit_amount" class="form-control" step="0.01" min="0"
                                           value="{{ old('deposit_amount', $order->deposit_amount) }}">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="deposit_paid" value="1" id="depositPaid"
                                           {{ old('deposit_paid', $order->deposit_paid) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="depositPaid">{{ __('messages.special_order.deposit_paid') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-pencil-square"></i> {{ __('messages.special_order.admin_notes') }}
                    </div>
                    <div class="card-body">
                        <textarea name="admin_notes" class="form-control" rows="4">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Order info (read-only) -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="bi bi-info-circle"></i> {{ __('messages.website_order.summary') }}
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>{{ __('messages.website_order.client') }}:</strong> {{ $order->shipping_full_name }}</p>
                        <p class="mb-1"><strong>{{ __('messages.special_order.store') }}:</strong> {{ $order->store?->name ?? '-' }}</p>
                        <p class="mb-1"><strong>{{ __('messages.website_order.total') }}:</strong> ${{ number_format($order->total, 2) }}</p>
                        <p class="mb-1"><strong>{{ __('messages.website_order.payment') }}:</strong>
                            <span class="badge bg-{{ \App\Models\WebsiteOrder::paymentStatusBadgeClass($order->payment_status) }}">
                                {{ __('messages.website_order.pay_status_' . $order->payment_status) }}
                            </span>
                        </p>
                        <p class="mb-0"><strong>{{ __('messages.special_order.payment_type_label') }}:</strong>
                            {{ __('messages.special_order.type_' . $order->payment_type) }}
                        </p>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> {{ __('messages.special_order.save_changes') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
