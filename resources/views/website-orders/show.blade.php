@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="crud_title mb-0">{{ __('messages.website_order.order_detail') }} {{ $order->order_number }}</h1>
        <a href="{{ route('website-orders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.website_order.back_to_list') }}
        </a>
    </div>

    <div class="row">
        <!-- Left column -->
        <div class="col-lg-8">
            <!-- Order summary card -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>{{ __('messages.website_order.summary') }}</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>{{ __('messages.website_order.order_number') }}:</strong> {{ $order->order_number }}<br>
                            <strong>{{ __('messages.website_order.date') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}<br>
                            <strong>{{ __('messages.website_order.locale') }}:</strong> {{ strtoupper($order->locale ?? '-') }}<br>
                            <strong>{{ __('messages.website_order.payment_method') }}:</strong> {{ $order->payment_method ?? '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('messages.website_order.client') }}:</strong> {{ $order->shipping_full_name }}<br>
                            <strong>{{ __('messages.website_order.email') }}:</strong>
                            @if($order->contact_email)
                                <a href="mailto:{{ $order->contact_email }}">{{ $order->contact_email }}</a>
                            @else
                                -
                            @endif
                            <br>
                            <strong>{{ __('messages.website_order.phone') }}:</strong> {{ $order->guest_phone ?? $order->shipping_phone ?? '-' }}<br>
                            <strong>{{ __('messages.website_order.status') }}:</strong>
                            <span class="badge bg-{{ \App\Models\WebsiteOrder::statusBadgeClass($order->status) }}">
                                {{ __('messages.website_order.status_' . $order->status) }}
                            </span>
                            <strong class="ms-2">{{ __('messages.website_order.payment') }}:</strong>
                            <span class="badge bg-{{ \App\Models\WebsiteOrder::paymentStatusBadgeClass($order->payment_status) }}">
                                {{ __('messages.website_order.pay_status_' . $order->payment_status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping address -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-geo-alt"></i> {{ __('messages.website_order.shipping_address') }}
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                        @if($order->shipping_company)
                            {{ $order->shipping_company }}<br>
                        @endif
                        {{ $order->shipping_address_line1 }}<br>
                        @if($order->shipping_address_line2)
                            {{ $order->shipping_address_line2 }}<br>
                        @endif
                        {{ $order->shipping_postal_code }} {{ $order->shipping_city }}<br>
                        @if($order->shipping_state)
                            {{ $order->shipping_state }}<br>
                        @endif
                        {{ $order->shipping_country }}
                        @if($order->shipping_phone)
                            <br><i class="bi bi-telephone"></i> {{ $order->shipping_phone }}
                        @endif
                    </p>
                </div>
            </div>

            <!-- Order items -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-cart3"></i> {{ __('messages.website_order.items') }} ({{ $order->items->count() }})
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.website_order.product') }}</th>
                                <th>{{ __('messages.website_order.sku') }}</th>
                                <th class="text-center">{{ __('messages.website_order.qty') }}</th>
                                <th class="text-end">{{ __('messages.website_order.unit_price') }}</th>
                                <th class="text-end">{{ __('messages.website_order.subtotal') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    @if($item->product_image)
                                        <img src="{{ asset('storage/' . $item->product_image) }}" alt="" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    @endif
                                    {{ $item->product_name }}
                                    @if($item->item_type !== 'product')
                                        <span class="badge bg-info">{{ $item->item_type }}</span>
                                    @endif
                                </td>
                                <td><code>{{ $item->product_sku ?? '-' }}</code></td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totals -->
            <div class="card mb-3">
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-end border-0"><strong>{{ __('messages.website_order.subtotal') }}</strong></td>
                            <td class="text-end border-0" style="width: 120px;">${{ number_format($order->subtotal, 2) }}</td>
                        </tr>
                        @if($order->shipping_cost > 0)
                        <tr>
                            <td class="text-end border-0">{{ __('messages.website_order.shipping') }}</td>
                            <td class="text-end border-0">${{ number_format($order->shipping_cost, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->tax > 0)
                        <tr>
                            <td class="text-end border-0">{{ __('messages.website_order.tax') }}</td>
                            <td class="text-end border-0">${{ number_format($order->tax, 2) }}</td>
                        </tr>
                        @endif
                        @if($order->discount > 0)
                        <tr>
                            <td class="text-end border-0">{{ __('messages.website_order.discount') }}</td>
                            <td class="text-end border-0 text-danger">-${{ number_format($order->discount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-end"><strong class="fs-5">{{ __('messages.website_order.total') }}</strong></td>
                            <td class="text-end"><strong class="fs-5">${{ number_format($order->total, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- PayWay transactions -->
            @if($order->transactions->count() > 0)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-credit-card"></i> {{ __('messages.website_order.transactions') }}
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('messages.website_order.tran_id') }}</th>
                                <th class="text-end">{{ __('messages.website_order.amount') }}</th>
                                <th>{{ __('messages.website_order.payment_option') }}</th>
                                <th class="text-center">{{ __('messages.website_order.payway_status') }}</th>
                                <th class="text-center">{{ __('messages.website_order.internal_status') }}</th>
                                <th>{{ __('messages.website_order.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->transactions as $txn)
                            <tr>
                                <td><code>{{ $txn->tran_id }}</code></td>
                                <td class="text-end">${{ number_format($txn->amount, 2) }} {{ $txn->currency }}</td>
                                <td>{{ $txn->payment_option ?? '-' }}</td>
                                <td class="text-center">
                                    <span title="Code: {{ $txn->status }}">{{ $txn->status_description }}</span>
                                    @if($txn->apv)
                                        <br><small class="text-muted">APV: {{ $txn->apv }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ \App\Models\WebsitePaymentTransaction::internalStatusBadgeClass($txn->internal_status) }}">
                                        {{ $txn->internal_status }}
                                    </span>
                                </td>
                                <td>
                                    {{ $txn->created_at->format('d/m/Y H:i') }}
                                    @if($txn->paid_at)
                                        <br><small class="text-success">{{ __('messages.website_order.paid_at') }}: {{ $txn->paid_at->format('d/m/Y H:i') }}</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Right column -->
        <div class="col-lg-4">
            <!-- Update status -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-arrow-repeat"></i> {{ __('messages.website_order.update_status') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('website-orders.update-status', $order) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <select name="status" class="form-select">
                                @foreach(\App\Models\WebsiteOrder::statuses() as $status)
                                    <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                        {{ __('messages.website_order.status_' . $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check"></i> {{ __('messages.website_order.save_status') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Customer notes (read-only) -->
            @if($order->customer_notes)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-chat-dots"></i> {{ __('messages.website_order.customer_notes') }}
                </div>
                <div class="card-body">
                    <p class="mb-0">{!! nl2br(e($order->customer_notes)) !!}</p>
                </div>
            </div>
            @endif

            <!-- Admin notes -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-pencil-square"></i> {{ __('messages.website_order.admin_notes') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('website-orders.update-notes', $order) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <textarea name="admin_notes" class="form-control" rows="4" placeholder="{{ __('messages.website_order.admin_notes_placeholder') }}">{{ $order->admin_notes }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-save"></i> {{ __('messages.website_order.save_notes') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
