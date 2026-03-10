@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h1 class="crud_title mb-0">{{ __('messages.special_order.list_title') }}</h1>
        <a href="{{ route('special-orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.special_order.create_btn') }}
        </a>
    </div>

    <!-- Summary badges -->
    <div class="d-flex gap-2 mb-3">
        <span class="badge bg-light text-dark border">{{ $counts['total'] }} {{ __('messages.website_order.total_orders') }}</span>
        <span class="badge bg-warning">{{ $counts['pending'] }} {{ __('messages.website_order.pending') }}</span>
        <span class="badge bg-primary">{{ $counts['processing'] }} {{ __('messages.website_order.processing') }}</span>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('special-orders.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="{{ __('messages.website_order.search_placeholder') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('messages.website_order.all_statuses') }}</option>
                @foreach(\App\Models\WebsiteOrder::statuses() as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ __('messages.website_order.status_' . $status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="payment_status" class="form-select" onchange="this.form.submit()">
                <option value="">{{ __('messages.website_order.all_payment_statuses') }}</option>
                @foreach(\App\Models\WebsiteOrder::paymentStatuses() as $ps)
                    <option value="{{ $ps }}" {{ request('payment_status') == $ps ? 'selected' : '' }}>
                        {{ __('messages.website_order.pay_status_' . $ps) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-search"></i> {{ __('messages.website_order.filter') }}
            </button>
            @if(request()->hasAny(['search', 'status', 'payment_status']))
                <a href="{{ route('special-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            @endif
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.website_order.order_number') }}</th>
                    <th>{{ __('messages.website_order.client') }}</th>
                    <th>{{ __('messages.special_order.store') }}</th>
                    <th class="text-center">{{ __('messages.special_order.payment_type_label') }}</th>
                    <th class="text-end">{{ __('messages.website_order.total') }}</th>
                    <th class="text-end">{{ __('messages.special_order.deposit') }}</th>
                    <th class="text-center">{{ __('messages.website_order.status') }}</th>
                    <th class="text-center">{{ __('messages.website_order.payment') }}</th>
                    <th>{{ __('messages.website_order.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="width: 1%; white-space: nowrap;">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('special-orders.show', $order) }}">
                                        <i class="bi bi-eye-fill"></i> {{ __('messages.website_order.view') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('special-orders.edit', $order) }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('special-orders.show', $order) }}" class="fw-bold text-decoration-none">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $order->shipping_full_name }}</strong><br>
                        <small class="text-muted">{{ $order->contact_email }}</small>
                    </td>
                    <td>
                        <small>{{ $order->store?->name ?? '-' }}</small>
                    </td>
                    <td class="text-center">
                        @if($order->payment_type === 'payment_link')
                            <span class="badge bg-info"><i class="bi bi-link-45deg"></i> {{ __('messages.special_order.type_payment_link') }}</span>
                        @elseif($order->payment_type === 'cash')
                            <span class="badge bg-success"><i class="bi bi-cash"></i> {{ __('messages.special_order.type_cash') }}</span>
                        @elseif($order->payment_type === 'bank_transfer')
                            <span class="badge bg-primary"><i class="bi bi-bank"></i> {{ __('messages.special_order.type_bank_transfer') }}</span>
                        @else
                            <span class="badge bg-secondary">-</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold">${{ number_format($order->total, 2) }}</td>
                    <td class="text-end">
                        @if($order->deposit_amount > 0)
                            <span class="{{ $order->deposit_paid ? 'text-success' : 'text-warning' }}">
                                ${{ number_format($order->deposit_amount, 2) }}
                                @if($order->deposit_paid)
                                    <i class="bi bi-check-circle-fill"></i>
                                @else
                                    <i class="bi bi-clock"></i>
                                @endif
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ \App\Models\WebsiteOrder::statusBadgeClass($order->status) }}">
                            {{ __('messages.website_order.status_' . $order->status) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ \App\Models\WebsiteOrder::paymentStatusBadgeClass($order->payment_status) }}">
                            {{ __('messages.website_order.pay_status_' . $order->payment_status) }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        {{ __('messages.website_order.no_orders') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
