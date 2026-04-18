@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="crud_title mb-0">
                {{ __('messages.special_order.order_detail') }} {{ $order->order_number }}
            </h1>
            @if($order->createdByUser)
                <small class="text-muted">{{ __('messages.special_order.created_by') }}: {{ $order->createdByUser->name }}</small>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('special-orders.invoice', $order) }}" class="btn btn-outline-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.special_order.invoice') }}
            </a>
            <a href="{{ route('special-orders.edit', $order) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
            </a>
            <a href="{{ route('special-orders.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.special_order.back_to_list') }}
            </a>
        </div>
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
                            <strong>{{ __('messages.special_order.store') }}:</strong> {{ $order->store?->name ?? '-' }}<br>
                            <strong>{{ __('messages.special_order.payment_type_label') }}:</strong>
                            @if($order->payment_type === 'payment_link')
                                <span class="badge bg-info">{{ __('messages.special_order.type_payment_link') }}</span>
                            @elseif($order->payment_type === 'cash')
                                <span class="badge bg-success">{{ __('messages.special_order.type_cash') }}</span>
                            @elseif($order->payment_type === 'bank_transfer')
                                <span class="badge bg-primary">{{ __('messages.special_order.type_bank_transfer') }}</span>
                            @endif
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
                            @if($order->paid_at)
                                <br><strong>{{ __('messages.special_order.payment_date') }}:</strong> {{ $order->paid_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping address -->
            @if($order->shipping_address_line1)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-geo-alt"></i> {{ __('messages.website_order.shipping_address') }}
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
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
            @endif

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
                            @foreach($order->items->where('item_type', 'product') as $item)
                            <tr>
                                <td>
                                    @if($item->product_image)
                                        <img src="{{ asset('storage/' . $item->product_image) }}" alt="" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                    @endif
                                    {{ $item->product_name }}
                                </td>
                                <td><code>{{ $item->product_sku ?? '-' }}</code></td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                            @endforeach
                            @foreach($order->items->where('item_type', 'option') as $item)
                            <tr class="table-info">
                                <td colspan="2">
                                    <i class="bi bi-plus-circle text-primary"></i>
                                    {{ $item->product_name }}
                                </td>
                                <td class="text-center">1</td>
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
                        @if($order->discount > 0)
                        <tr>
                            <td class="text-end border-0">{{ __('messages.special_order.subtotal') }}</td>
                            <td class="text-end border-0" style="width: 150px;">${{ number_format($order->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-end border-0 text-danger">{{ __('messages.special_order.discount') }}</td>
                            <td class="text-end border-0 text-danger" style="width: 150px;">-${{ number_format($order->discount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-end border-0"><strong class="fs-5">{{ __('messages.website_order.total') }}</strong></td>
                            <td class="text-end border-0" style="width: 150px;"><strong class="fs-5">${{ number_format($order->total, 2) }}</strong></td>
                        </tr>
                        @if($order->deposit_amount > 0)
                        <tr>
                            <td class="text-end border-0">
                                {{ __('messages.special_order.deposit') }}
                                @if($order->deposit_paid)
                                    <span class="badge bg-success">{{ __('messages.special_order.deposit_paid') }}</span>
                                @else
                                    <span class="badge bg-warning">{{ __('messages.special_order.deposit_not_paid') }}</span>
                                @endif
                            </td>
                            <td class="text-end border-0">${{ number_format($order->deposit_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="text-end border-0"><strong>{{ __('messages.special_order.remaining_balance') }}</strong></td>
                            <td class="text-end border-0"><strong>${{ number_format($order->remaining_balance, 2) }}</strong></td>
                        </tr>
                        @endif
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
                                    <span class="badge bg-{{ \App\Models\WebsitePaymentTransaction::internalStatusBadgeClass($txn->internal_status) }}">
                                        {{ $txn->internal_status }}
                                    </span>
                                </td>
                                <td>{{ $txn->created_at->format('d/m/Y H:i') }}</td>
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
                    <form action="{{ route('special-orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-2">
                            <select name="status" class="form-select">
                                @foreach(\App\Models\WebsiteOrder::statuses() as $status)
                                    <option value="{{ $status }}" {{ $order->status === $status ? 'selected' : '' }}>
                                        {{ __('messages.website_order.status_' . $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">
                                <i class="bi bi-truck"></i> {{ __('messages.website_order.tracking_url') }}
                            </label>
                            <input type="url" name="tracking_url" class="form-control form-control-sm"
                                   value="{{ $order->tracking_url }}"
                                   placeholder="{{ __('messages.website_order.tracking_url_placeholder') }}">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check"></i> {{ __('messages.website_order.save_status') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Deposit info -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-piggy-bank"></i> {{ __('messages.special_order.deposit') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('special-orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $order->status }}">
                        <div class="mb-2">
                            <label class="form-label small">{{ __('messages.special_order.deposit_amount') }}</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="deposit_amount" class="form-control" step="0.00001" min="0"
                                       value="{{ $order->deposit_amount }}">
                            </div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="deposit_paid" value="1" id="depositPaid"
                                   {{ $order->deposit_paid ? 'checked' : '' }}>
                            <label class="form-check-label" for="depositPaid">{{ __('messages.special_order.deposit_paid') }}</label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-save"></i> {{ __('messages.special_order.save_deposit') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mark as paid (bank transfer or cash — available for any payment type) -->
            @if($order->payment_status !== 'paid')
            <div class="card mb-3 border-success">
                <div class="card-body">
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                        <i class="bi bi-check-circle"></i> {{ __('messages.special_order.mark_as_paid') }}
                    </button>
                    <small class="text-muted d-block mt-1 text-center">
                        {{ __('messages.special_order.mark_paid_info') }}
                    </small>
                </div>
            </div>

            <!-- Mark as Paid Modal -->
            <div class="modal fade" id="markPaidModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('special-orders.mark-paid', $order) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-check-circle text-success"></i> {{ __('messages.special_order.mark_as_paid') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted">{{ __('messages.special_order.mark_paid_info') }}</p>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.special_order.payment_type_label') }} *</label>
                                    <select name="payment_type" class="form-select">
                                        <option value="bank_transfer" {{ $order->payment_type === 'bank_transfer' ? 'selected' : '' }}>{{ __('messages.special_order.type_bank_transfer') }}</option>
                                        <option value="cash" {{ $order->payment_type === 'cash' ? 'selected' : '' }}>{{ __('messages.special_order.type_cash') }}</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{ __('messages.special_order.payment_date') }} *</label>
                                    <input type="date" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                                <div class="alert alert-light border mb-0">
                                    <strong>{{ __('messages.website_order.total') }}:</strong> ${{ number_format($order->total, 2) }}
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> {{ __('messages.special_order.confirm_payment') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment Link (only for payment_link type) -->
            @if($order->payment_type === 'payment_link')
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-link-45deg"></i> {{ __('messages.special_order.payment_link') }}
                </div>
                <div class="card-body">
                    @if($order->payment_link_url)
                        <div class="mb-2">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" value="{{ $order->payment_link_url }}" id="paymentLinkUrl" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('paymentLinkUrl').value);this.innerHTML='<i class=\'bi bi-check\'></i>';">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        @if($order->payment_link_expires_at)
                            <small class="d-block mb-2 {{ $order->payment_link_expired ? 'text-danger' : 'text-muted' }}">
                                @if($order->payment_link_expired)
                                    <i class="bi bi-exclamation-triangle"></i> {{ __('messages.special_order.link_expired') }}
                                @else
                                    <i class="bi bi-clock"></i> {{ __('messages.special_order.link_expires') }}:
                                    {{ $order->payment_link_expires_at->format('d/m/Y H:i') }}
                                @endif
                            </small>
                        @endif
                    @else
                        <p class="text-muted mb-2">{{ __('messages.special_order.no_link') }}</p>
                    @endif

                    @if($order->payment_status !== 'paid')
                        <div class="d-flex gap-2">
                            <form action="{{ route('special-orders.regenerate-link', $order) }}" method="POST" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-arrow-repeat"></i> {{ __('messages.special_order.regenerate_link') }}
                                </button>
                            </form>
                            @if($order->payment_link_url && $order->contact_email)
                                <form action="{{ route('special-orders.send-link-email', $order) }}" method="POST" class="flex-fill">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success btn-sm w-100">
                                        <i class="bi bi-envelope"></i> {{ __('messages.special_order.send_email') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Admin notes -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <i class="bi bi-pencil-square"></i> {{ __('messages.special_order.admin_notes') }}
                </div>
                <div class="card-body">
                    <form action="{{ route('special-orders.update', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $order->status }}">
                        <div class="mb-2">
                            <textarea name="admin_notes" class="form-control" rows="4" placeholder="{{ __('messages.special_order.admin_notes_placeholder') }}">{{ $order->admin_notes }}</textarea>
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
