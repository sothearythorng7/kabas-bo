@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.exchange.details') }} #{{ $exchange->id }}</h1>
        <a href="{{ route('exchanges.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.exchange.info') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">{{ __('messages.exchange.id') }}</th>
                            <td>{{ $exchange->id }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.exchange.original_sale') }}</th>
                            <td>
                                <a href="#">#{{ $exchange->original_sale_id }}</a>
                                <small class="text-muted">({{ $exchange->originalSale?->created_at?->format('d/m/Y') }})</small>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.store.name') }}</th>
                            <td>{{ $exchange->store?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.user.name') }}</th>
                            <td>{{ $exchange->user?->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.date') }}</th>
                            <td>{{ $exchange->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($exchange->notes)
                        <tr>
                            <th>{{ __('messages.exchange.notes') }}</th>
                            <td>{{ $exchange->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">{{ __('messages.exchange.returned_items') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.product.name') }}</th>
                                <th class="text-center">{{ __('messages.quantity') }}</th>
                                <th class="text-end">{{ __('messages.exchange.unit_price') }}</th>
                                <th class="text-end">{{ __('messages.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exchange->items->where('type', 'returned') as $item)
                            <tr>
                                <td>{{ $item->product?->name['fr'] ?? $item->product?->name['en'] ?? 'Product #' . $item->product_id }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }} $</td>
                                <td class="text-end">{{ number_format($item->total_price, 2) }} $</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-danger">
                                <th colspan="3" class="text-end">{{ __('messages.exchange.return_total') }}</th>
                                <th class="text-end">{{ number_format($exchange->return_total, 2) }} $</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @php
                $newItems = $exchange->items->where('type', 'new');
            @endphp
            @if($newItems->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">{{ __('messages.exchange.new_items') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('messages.product.name') }}</th>
                                <th class="text-center">{{ __('messages.quantity') }}</th>
                                <th class="text-end">{{ __('messages.exchange.unit_price') }}</th>
                                <th class="text-end">{{ __('messages.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($newItems as $item)
                            <tr>
                                <td>{{ $item->product?->name['fr'] ?? $item->product?->name['en'] ?? 'Product #' . $item->product_id }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->unit_price, 2) }} $</td>
                                <td class="text-end">{{ number_format($item->total_price, 2) }} $</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th colspan="3" class="text-end">{{ __('messages.exchange.new_items_total') }}</th>
                                <th class="text-end">{{ number_format($exchange->new_items_total, 2) }} $</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.exchange.summary') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('messages.exchange.return_total') }}</th>
                            <td class="text-end text-danger fs-5">{{ number_format($exchange->return_total, 2) }} $</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.exchange.new_items_total') }}</th>
                            <td class="text-end text-success fs-5">{{ number_format($exchange->new_items_total, 2) }} $</td>
                        </tr>
                        <tr class="border-top">
                            <th>{{ __('messages.exchange.balance') }}</th>
                            <td class="text-end fs-4 fw-bold">
                                @if($exchange->balance > 0)
                                    <span class="text-success">+{{ number_format($exchange->balance, 2) }} $</span>
                                    <br><small class="text-muted">{{ __('messages.exchange.customer_credit') }}</small>
                                @elseif($exchange->balance < 0)
                                    <span class="text-danger">{{ number_format($exchange->balance, 2) }} $</span>
                                    <br><small class="text-muted">{{ __('messages.exchange.customer_owes') }}</small>
                                @else
                                    <span class="text-muted">0.00 $</span>
                                    <br><small class="text-muted">{{ __('messages.exchange.even') }}</small>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($exchange->generatedVoucher)
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">{{ __('messages.exchange.generated_voucher') }}</h5>
                </div>
                <div class="card-body text-center">
                    <div class="border rounded p-4 bg-light">
                        <h6 class="text-muted mb-2">{{ __('messages.voucher.code') }}</h6>
                        <h2><code>{{ $exchange->generatedVoucher->code }}</code></h2>
                        <h4 class="text-success">{{ number_format($exchange->generatedVoucher->amount, 2) }} $</h4>
                        <p class="text-muted mb-0">
                            {{ __('messages.voucher.valid_until') }}: {{ $exchange->generatedVoucher->expires_at->format('d/m/Y') }}
                        </p>
                        <a href="{{ route('vouchers.show', $exchange->generatedVoucher) }}" class="btn btn-outline-primary mt-3">
                            {{ __('messages.voucher.view_details') }}
                        </a>
                    </div>
                </div>
            </div>
            @endif

            @if($exchange->payment_method)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.exchange.payment_received') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('messages.exchange.payment_method') }}</th>
                            <td>{{ ucfirst($exchange->payment_method) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.exchange.payment_amount') }}</th>
                            <td>{{ number_format($exchange->payment_amount, 2) }} $</td>
                        </tr>
                        @if($exchange->paymentVoucher)
                        <tr>
                            <th>{{ __('messages.exchange.payment_voucher') }}</th>
                            <td>
                                <a href="{{ route('vouchers.show', $exchange->paymentVoucher) }}">
                                    <code>{{ $exchange->paymentVoucher->code }}</code>
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
            @endif

            @if($exchange->financialTransaction)
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Transaction financière</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>ID Transaction</th>
                            <td>#{{ $exchange->financialTransaction->id }}</td>
                        </tr>
                        <tr>
                            <th>Direction</th>
                            <td>
                                @if($exchange->financialTransaction->direction === 'credit')
                                    <span class="badge bg-success">Crédit (entrée)</span>
                                @else
                                    <span class="badge bg-danger">Débit (sortie)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Montant</th>
                            <td class="{{ $exchange->financialTransaction->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $exchange->financialTransaction->direction === 'credit' ? '+' : '-' }}{{ number_format($exchange->financialTransaction->amount, 2) }} €
                            </td>
                        </tr>
                        <tr>
                            <th>Label</th>
                            <td>{{ $exchange->financialTransaction->label }}</td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{ $exchange->financialTransaction->transaction_date->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                    <a href="{{ route('financial.transactions.index') }}?search=exchange_{{ $exchange->id }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-eye"></i> Voir dans le journal
                    </a>
                </div>
            </div>
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.exchange.original_sale_info') }}</h5>
                </div>
                <div class="card-body">
                    @if($exchange->originalSale)
                    <table class="table table-borderless">
                        <tr>
                            <th>{{ __('messages.sale.id') }}</th>
                            <td>#{{ $exchange->originalSale->id }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.date') }}</th>
                            <td>{{ $exchange->originalSale->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.sale.total') }}</th>
                            <td>{{ number_format($exchange->originalSale->total, 2) }} $</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.sale.payment_type') }}</th>
                            <td>{{ $exchange->originalSale->payment_type }}</td>
                        </tr>
                    </table>

                    <h6 class="mt-3">{{ __('messages.exchange.original_items') }}</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('messages.product.name') }}</th>
                                <th class="text-center">{{ __('messages.quantity') }}</th>
                                <th class="text-end">{{ __('messages.price') }}</th>
                                <th>{{ __('messages.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exchange->originalSale->items as $item)
                            <tr class="{{ $item->exchanged_at ? 'table-secondary text-decoration-line-through' : '' }}">
                                <td>
                                    {{ $item->product?->name['fr'] ?? $item->product?->name['en'] ?? 'Product #' . $item->product_id }}
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->price, 2) }} $</td>
                                <td>
                                    @if($item->exchanged_at)
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-left-right"></i> Échangé
                                        </span>
                                    @elseif($item->added_via_exchange_id)
                                        <span class="badge bg-info">
                                            <i class="bi bi-plus-circle"></i> Via échange
                                        </span>
                                    @else
                                        <span class="badge bg-success">{{ __('messages.exchange.available') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted">{{ __('messages.exchange.sale_not_found') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
