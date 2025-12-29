@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.financial.transaction_detail') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')

    <div class="card">
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="transactionTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                        <i class="bi bi-info-circle"></i> {{ __('messages.financial.details') }}
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="debug-tab" data-bs-toggle="tab" data-bs-target="#debug" type="button" role="tab">
                        <i class="bi bi-bug"></i> Debug
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="transactionTabsContent">
                <!-- Details Tab -->
                <div class="tab-pane fade show active" id="details" role="tabpanel">
                    <h5 class="card-title">{{ $transaction->label }}</h5>

                    <table class="table table-sm">
                <tr>
                    <th>{{ __('messages.financial.date') }}</th>
                    <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.account') }}</th>
                    <td>{{ $transaction->account->code }} - {{ $transaction->account->name }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.type') }}</th>
                    <td>{{ ucfirst($transaction->direction) }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.amount') }}</th>
                    <td class="{{ $transaction->direction === 'debit' ? 'text-danger' : 'text-success' }}">
                        {{ $transaction->direction === 'debit' ? '-' : '+' }} {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                    </td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.payment_method') }}</th>
                    <td>{{ $transaction->paymentMethod->name }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.balance_before') }}</th>
                    <td>{{ number_format($transaction->balance_before, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.balance_after') }}</th>
                    <td>{{ number_format($transaction->balance_after, 2) }} {{ $transaction->currency }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.user') }}</th>
                    <td>{{ $transaction->user?->name }}</td>
                </tr>
                <tr>
                    <th>{{ __('messages.financial.status') }}</th>
                    <td>{{ ucfirst($transaction->status) }}</td>
                </tr>
                @if($transaction->external_reference && !$transaction->sale)
                <tr>
                    <th>{{ __('messages.financial.order_link') }}</th>
                    <td><a href="{{ url($transaction->external_reference) }}" class="btn btn-success btn-sm">{{ __('messages.financial.view_order') }}</a></td>
                </tr>
                @endif
            </table>

            @if($transaction->sale)
                <h5 class="mt-4"><i class="bi bi-cart"></i> {{ __('messages.financial.pos_sale_details') }}</h5>
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <strong>{{ __('messages.financial.sale_id') }}:</strong><br>
                                #{{ $transaction->sale->id }}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('messages.financial.date') }}:</strong><br>
                                {{ $transaction->sale->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('messages.financial.payment_method') }}:</strong><br>
                                {{ ucfirst($transaction->sale->payment_type) }}
                            </div>
                            <div class="col-md-3">
                                <strong>{{ __('messages.financial.total') }}:</strong><br>
                                <span class="text-success fw-bold">{{ number_format($transaction->sale->total, 2) }} $</span>
                            </div>
                        </div>

                        @if($transaction->sale->shift)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>{{ __('messages.financial.shift') }}:</strong><br>
                                #{{ $transaction->sale->shift->id }} - {{ $transaction->sale->shift->user->name ?? 'N/A' }}
                                ({{ $transaction->sale->shift->started_at?->format('d/m/Y H:i') }})
                            </div>
                        </div>
                        @endif

                        @if($transaction->sale->items->count())
                            <h6 class="mt-3">{{ __('messages.financial.items') }}</h6>
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.financial.product') }}</th>
                                        <th>{{ __('messages.financial.ean') }}</th>
                                        <th class="text-center">{{ __('messages.financial.quantity') }}</th>
                                        <th class="text-end">{{ __('messages.financial.unit_price') }}</th>
                                        <th class="text-end">{{ __('messages.financial.discount') }}</th>
                                        <th class="text-end">{{ __('messages.financial.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $grandTotal = 0; @endphp
                                    @foreach($transaction->sale->items as $item)
                                        @php
                                            $itemDiscounts = collect($item->discounts ?? [])->sum('amount');
                                            $unitPrice = $item->price ?? 0;
                                            $quantity = $item->quantity ?? 1;
                                            $lineTotal = ($unitPrice * $quantity) - $itemDiscounts;
                                            $grandTotal += $lineTotal;
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($item->product)
                                                    {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                                                @elseif($item->is_delivery)
                                                    <i class="bi bi-truck text-success"></i> {{ __('messages.financial.delivery_service') }}
                                                    @if($item->delivery_address)
                                                        <br><small class="text-muted">{{ $item->delivery_address }}</small>
                                                    @endif
                                                @elseif($item->is_custom_service)
                                                    <i class="bi bi-gear text-primary"></i> {{ __('messages.financial.custom_service') }}
                                                    @if($item->custom_service_description)
                                                        <br><small class="text-muted">{{ $item->custom_service_description }}</small>
                                                    @endif
                                                @else
                                                    {{ __('messages.financial.unknown_item') }}
                                                @endif
                                            </td>
                                            <td>{{ $item->product?->ean ?? '-' }}</td>
                                            <td class="text-center">{{ $quantity }}</td>
                                            <td class="text-end">{{ number_format($unitPrice, 2) }} $</td>
                                            <td class="text-end">{{ number_format($itemDiscounts, 2) }} $</td>
                                            <td class="text-end fw-bold">{{ number_format($lineTotal, 2) }} $</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <th colspan="5" class="text-end">{{ __('messages.financial.total') }}</th>
                                        <th class="text-end">{{ number_format($transaction->sale->total, 2) }} $</th>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif

                        @if($transaction->sale->discounts && count($transaction->sale->discounts) > 0)
                            <h6 class="mt-3">{{ __('messages.financial.discounts_applied') }}</h6>
                            <ul class="list-group">
                                @foreach($transaction->sale->discounts as $discount)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $discount['name'] ?? 'Discount' }}
                                        <span class="badge bg-warning text-dark">
                                            @if(($discount['type'] ?? '') === 'percent')
                                                {{ $discount['value'] }}%
                                            @else
                                                {{ number_format($discount['amount'] ?? $discount['value'] ?? 0, 2) }} $
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endif

            @if($transaction->attachments->count())
                <h5 class="mt-4">{{ __('messages.financial.attachments') }}</h5>
                <ul class="list-group">
                    @foreach($transaction->attachments as $file)
                        <li class="list-group-item">
                            <a href="{{ $file->url }}" target="_blank"><i class="bi bi-card-image" style="font-size:5em;"></i></a>
                        </li>
                    @endforeach
                </ul>
            @endif
                </div>

                <!-- Debug Tab -->
                <div class="tab-pane fade" id="debug" role="tabpanel">
                    <h5 class="mb-3"><i class="bi bi-database"></i> Technical Information</h5>

                    <!-- Financial Transaction -->
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            <strong>FinancialTransaction</strong> <code class="text-light">#{{ $transaction->id }}</code>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody>
                                    <tr><th style="width:200px">id</th><td>{{ $transaction->id }}</td></tr>
                                    <tr><th>store_id</th><td>{{ $transaction->store_id }}</td></tr>
                                    <tr><th>account_id</th><td>{{ $transaction->account_id }}</td></tr>
                                    <tr><th>payment_method_id</th><td>{{ $transaction->payment_method_id }}</td></tr>
                                    <tr><th>user_id</th><td>{{ $transaction->user_id }}</td></tr>
                                    <tr><th>amount</th><td>{{ $transaction->amount }}</td></tr>
                                    <tr><th>currency</th><td>{{ $transaction->currency }}</td></tr>
                                    <tr><th>direction</th><td>{{ $transaction->direction }}</td></tr>
                                    <tr><th>balance_before</th><td>{{ $transaction->balance_before }}</td></tr>
                                    <tr><th>balance_after</th><td>{{ $transaction->balance_after }}</td></tr>
                                    <tr><th>label</th><td>{{ $transaction->label }}</td></tr>
                                    <tr><th>description</th><td><code>{{ $transaction->description }}</code></td></tr>
                                    <tr><th>status</th><td>{{ $transaction->status }}</td></tr>
                                    <tr><th>external_reference</th><td>{{ $transaction->external_reference }}</td></tr>
                                    <tr><th>transaction_date</th><td>{{ $transaction->transaction_date }}</td></tr>
                                    <tr><th>created_at</th><td>{{ $transaction->created_at }}</td></tr>
                                    <tr><th>updated_at</th><td>{{ $transaction->updated_at }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($transaction->sale)
                    <!-- Sale -->
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <strong>Sale</strong> <code class="text-light">#{{ $transaction->sale->id }}</code>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered mb-3">
                                <tbody>
                                    <tr><th style="width:200px">id</th><td>{{ $transaction->sale->id }}</td></tr>
                                    <tr><th>shift_id</th><td>{{ $transaction->sale->shift_id }}</td></tr>
                                    <tr><th>store_id</th><td>{{ $transaction->sale->store_id }}</td></tr>
                                    <tr><th>financial_transaction_id</th><td>{{ $transaction->sale->financial_transaction_id }}</td></tr>
                                    <tr><th>payment_type</th><td>{{ $transaction->sale->payment_type }}</td></tr>
                                    <tr><th>total</th><td>{{ $transaction->sale->total }}</td></tr>
                                    <tr><th>synced_at</th><td>{{ $transaction->sale->synced_at }}</td></tr>
                                    <tr><th>created_at</th><td>{{ $transaction->sale->created_at }}</td></tr>
                                    <tr><th>updated_at</th><td>{{ $transaction->sale->updated_at }}</td></tr>
                                </tbody>
                            </table>

                            <h6>discounts (JSON)</h6>
                            <pre class="bg-dark text-light p-2 rounded" style="max-height:200px;overflow:auto"><code>{{ json_encode($transaction->sale->discounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>

                            <h6>split_payments (JSON)</h6>
                            <pre class="bg-dark text-light p-2 rounded" style="max-height:200px;overflow:auto"><code>{{ json_encode($transaction->sale->split_payments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>

                    <!-- Shift -->
                    @if($transaction->sale->shift)
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <strong>Shift</strong> <code class="text-light">#{{ $transaction->sale->shift->id }}</code>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody>
                                    <tr><th style="width:200px">id</th><td>{{ $transaction->sale->shift->id }}</td></tr>
                                    <tr><th>user_id</th><td>{{ $transaction->sale->shift->user_id }} ({{ $transaction->sale->shift->user->name ?? 'N/A' }})</td></tr>
                                    <tr><th>store_id</th><td>{{ $transaction->sale->shift->store_id }}</td></tr>
                                    <tr><th>opening_cash</th><td>{{ $transaction->sale->shift->opening_cash }}</td></tr>
                                    <tr><th>closing_cash</th><td>{{ $transaction->sale->shift->closing_cash }}</td></tr>
                                    <tr><th>cash_in</th><td>{{ $transaction->sale->shift->cash_in }}</td></tr>
                                    <tr><th>cash_out</th><td>{{ $transaction->sale->shift->cash_out }}</td></tr>
                                    <tr><th>cash_difference</th><td>{{ $transaction->sale->shift->cash_difference }}</td></tr>
                                    <tr><th>visitors_count</th><td>{{ $transaction->sale->shift->visitors_count }}</td></tr>
                                    <tr><th>started_at</th><td>{{ $transaction->sale->shift->started_at }}</td></tr>
                                    <tr><th>ended_at</th><td>{{ $transaction->sale->shift->ended_at }}</td></tr>
                                    <tr><th>synced</th><td>{{ $transaction->sale->shift->synced ? 'true' : 'false' }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Sale Items -->
                    @if($transaction->sale->items->count())
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-dark">
                            <strong>SaleItems</strong> <span class="badge bg-dark">{{ $transaction->sale->items->count() }}</span>
                        </div>
                        <div class="card-body">
                            @foreach($transaction->sale->items as $item)
                            <div class="border rounded p-2 mb-2">
                                <table class="table table-sm table-bordered mb-2">
                                    <tbody>
                                        <tr><th style="width:200px">id</th><td>{{ $item->id }}</td></tr>
                                        <tr><th>sale_id</th><td>{{ $item->sale_id }}</td></tr>
                                        <tr><th>product_id</th><td>{{ $item->product_id }}</td></tr>
                                        <tr><th>quantity</th><td>{{ $item->quantity }}</td></tr>
                                        <tr><th>price</th><td>{{ $item->price }}</td></tr>
                                        <tr><th>is_delivery</th><td>{{ $item->is_delivery ? 'true' : 'false' }}</td></tr>
                                        <tr><th>delivery_address</th><td>{{ $item->delivery_address }}</td></tr>
                                        <tr><th>is_custom_service</th><td>{{ $item->is_custom_service ? 'true' : 'false' }}</td></tr>
                                        <tr><th>custom_service_description</th><td>{{ $item->custom_service_description }}</td></tr>
                                    </tbody>
                                </table>
                                <h6>discounts (JSON)</h6>
                                <pre class="bg-dark text-light p-2 rounded" style="max-height:150px;overflow:auto"><code>{{ json_encode($item->discounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('financial.transactions.edit', [$store->id, $transaction->id]) }}" class="btn btn-warning">{{ __('messages.financial.edit') }}</a>
        <a href="{{ route('financial.transactions.index', $store->id) }}" class="btn btn-secondary">{{ __('messages.financial.back') }}</a>
    </div>
</div>
@endsection
