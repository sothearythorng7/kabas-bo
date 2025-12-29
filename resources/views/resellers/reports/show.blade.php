@extends('layouts.app')

@section('content')
@php
    $resellerParam = ($reseller->is_shop ?? false) ? 'shop-'.$reseller->id : $reseller->id;
@endphp
<div class="container mt-4">
    <h1>{{ __('messages.resellers.sales_report') }} #{{ $report->id }} {{ __('messages.resellers.for') }} {{ $reseller->name }}</h1>
    <p><strong>{{ __('messages.resellers.created_at') }}:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</p>

    <div class="mb-3">
        <a href="{{ route('resellers.show', $resellerParam ?? $reseller->id) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.resellers.back_to_reseller') }}
        </a>
    </div>

    {{-- Paiements --}}
    @if($report->invoice)
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.resellers.total_to_pay') }}</h6>
                    <p class="card-text">${{ number_format($report->invoice->total_amount, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card text-center bg-info">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.resellers.total_already_paid') }}</h6>
                    <p class="card-text">${{ number_format($totalPaid, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card text-center bg-warning">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.resellers.remaining_to_pay') }}</h6>
                    <p class="card-text">${{ number_format($remaining, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card text-center bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">{{ __('messages.resellers.payment_status') }}</h6>
                    <p class="card-text">
                        @if($paymentStatus === 'paid')
                            <span class="badge bg-success">{{ __('messages.resellers.paid') }}</span>
                        @elseif($paymentStatus === 'partially_paid')
                            <span class="badge bg-warning text-dark">{{ __('messages.resellers.partially_paid') }}</span>
                        @elseif($paymentStatus === 'unpaid')
                            <span class="badge bg-danger">{{ __('messages.resellers.unpaid') }}</span>
                        @else
                            N/A
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Liste des produits vendus --}}
    <div class="table-responsive mb-3">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{ __('messages.resellers.ean') }}</th>
                    <th>{{ __('messages.resellers.product_name') }}</th>
                    <th>{{ __('messages.resellers.unit_price') }}</th>
                    <th>{{ __('messages.resellers.quantity_sold') }}</th>
                    <th>{{ __('messages.resellers.total_value') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->items as $item)
                    <tr>
                        <td>{{ $item->product->ean ?? '-' }}</td>
                        <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                        <td>{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                        <td>{{ $item->quantity_sold }}</td>
                        <td>{{ number_format($item->quantity_sold * $item->unit_price, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">{{ __('messages.resellers.total_report_value') }}</th>
                    <th>
                        {{ number_format($report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price), 2, ',', ' ') }} $
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Liste des paiements --}}
    @if($report->invoice && $paymentsCount > 0)
        <!-- Desktop -->
        <div class="d-none d-md-block">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.resellers.date') }}</th>
                        <th>{{ __('messages.resellers.amount') }}</th>
                        <th>{{ __('messages.resellers.method') }}</th>
                        <th>{{ __('messages.resellers.reference') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report->invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>{{ $payment->reference }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="d-md-none">
            <div class="row">
                @foreach($report->invoice->payments as $payment)
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <p class="mb-1"><strong>{{ __('messages.resellers.date') }} :</strong> {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                                <p class="mb-1"><strong>{{ __('messages.resellers.amount') }} :</strong> {{ number_format($payment->amount, 2) }}</p>
                                <p class="mb-1"><strong>{{ __('messages.resellers.method') }} :</strong> {{ ucfirst($payment->payment_method) }}</p>
                                <p class="mb-1"><strong>{{ __('messages.resellers.reference') }} :</strong> {{ $payment->reference }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($report->invoice)
        <div class="alert alert-info">
            {{ __('messages.resellers.no_payments') }}
        </div>
    @endif

    {{-- Bouton modal pour ajouter paiement --}}
    @if($report->invoice)
        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
            {{ __('messages.resellers.add_payment') }}
        </button>
    @endif
</div>
{{-- Modal ajout paiement --}}
@if($report->invoice)
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('resellers.report.addPayment', ['reseller' => $resellerParam ?? $reseller->id, 'report' => $report]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">{{ __('messages.resellers.add_payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>{{ __('messages.resellers.total_amount_label') }} :</strong> {{ number_format($report->invoice->total_amount, 2) }}</p>
                    <p><strong>{{ __('messages.resellers.already_paid') }} :</strong> {{ number_format($totalPaid, 2) }}</p>
                    <p><strong>{{ __('messages.resellers.remaining') }} :</strong> {{ number_format($remaining, 2) }}</p>

                    <div class="mb-3">
                        <label>{{ __('messages.resellers.amount') }}</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control" max="{{ $remaining }}" required>
                        <div id="amountWarning" class="text-danger mt-1" style="display:none;">
                            {{ __('messages.resellers.amount_cannot_exceed') }} {{ number_format($remaining, 2) }}.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>{{ __('messages.resellers.method') }}</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash">{{ __('messages.resellers.cash') }}</option>
                            <option value="transfer">{{ __('messages.resellers.transfer') }}</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>{{ __('messages.resellers.reference') }}</label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('messages.resellers.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('paymentAmount')?.addEventListener('input', function() {
    const max = {{ $remaining }};
    const warning = document.getElementById('amountWarning');
    if (parseFloat(this.value) > max) {
        this.value = max;
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
});
</script>
@endpush
@endif
@endsection
