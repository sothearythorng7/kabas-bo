@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">
            {{ __('messages.stock_loss.show_title') }} {{ $stockLoss->reference }}
            @switch($stockLoss->status)
                @case('draft')
                    <span class="badge bg-secondary">{{ __('messages.stock_loss.status_draft') }}</span>
                    @break
                @case('validated')
                    <span class="badge bg-success">{{ __('messages.stock_loss.status_validated') }}</span>
                    @break
                @case('refund_requested')
                    <span class="badge bg-info">{{ __('messages.stock_loss.status_refund_requested') }}</span>
                    @break
                @case('refund_received')
                    <span class="badge bg-primary">{{ __('messages.stock_loss.status_refund_received') }}</span>
                    @break
            @endswitch
        </h1>
        <a href="{{ route('stock-losses.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.stock_loss.details') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.stock_loss.reference') }}:</strong> {{ $stockLoss->reference }}</p>
                    <p><strong>{{ __('messages.stock_loss.store') }}:</strong> {{ $stockLoss->store->name }}</p>
                    <p>
                        <strong>{{ __('messages.stock_loss.type') }}:</strong>
                        @if($stockLoss->isPureLoss())
                            <span class="badge bg-danger">{{ __('messages.stock_loss.type_pure_loss') }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ __('messages.stock_loss.type_supplier_refund') }}</span>
                        @endif
                    </p>
                    @if($stockLoss->isSupplierRefund() && $stockLoss->supplier)
                        <p><strong>{{ __('messages.stock_loss.supplier') }}:</strong> {{ $stockLoss->supplier->name }}</p>
                    @endif
                    @if($stockLoss->reason)
                        <p><strong>{{ __('messages.stock_loss.reason') }}:</strong> {{ $stockLoss->reason }}</p>
                    @endif
                    @if($stockLoss->notes)
                        <p><strong>{{ __('messages.stock_loss.notes') }}:</strong> {{ $stockLoss->notes }}</p>
                    @endif
                    <p><strong>{{ __('messages.common.date') }}:</strong> {{ $stockLoss->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>{{ __('messages.stock_loss.created_by') }}:</strong> {{ $stockLoss->createdBy?->name ?? '-' }}</p>
                    @if($stockLoss->validated_at)
                        <p><strong>{{ __('messages.stock_loss.validated_at') }}:</strong> {{ $stockLoss->validated_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.stock_loss.summary') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.stock_loss.total_products') }}:</strong> {{ $stockLoss->items->count() }}</p>
                    <p><strong>{{ __('messages.stock_loss.total_quantity') }}:</strong> {{ $stockLoss->total_quantity }}</p>
                    <p><strong>{{ __('messages.stock_loss.total_value') }}:</strong> ${{ number_format($stockLoss->total_value, 2) }}</p>

                    @if($stockLoss->financialTransaction)
                        <hr>
                        <p><strong>{{ __('messages.stock_loss.financial_transaction') }}:</strong>
                            ${{ number_format($stockLoss->financialTransaction->amount, 2) }} ({{ $stockLoss->financialTransaction->direction }})
                        </p>
                    @endif

                    @if($stockLoss->isSupplierRefund())
                        <hr>
                        @if($stockLoss->refund_requested_at)
                            <p><strong>{{ __('messages.stock_loss.refund_requested_at') }}:</strong> {{ $stockLoss->refund_requested_at->format('d/m/Y H:i') }}</p>
                        @endif
                        @if($stockLoss->refund_received_at)
                            <p><strong>{{ __('messages.stock_loss.refund_received_at') }}:</strong> {{ $stockLoss->refund_received_at->format('d/m/Y H:i') }}</p>
                            <p><strong>{{ __('messages.stock_loss.refund_amount') }}:</strong> ${{ number_format($stockLoss->refund_amount, 2) }}</p>
                        @endif
                        @if($stockLoss->refundTransaction)
                            <p><strong>{{ __('messages.stock_loss.refund_transaction') }}:</strong>
                                ${{ number_format($stockLoss->refundTransaction->amount, 2) }} ({{ $stockLoss->refundTransaction->direction }})
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('messages.stock_loss.products') }}</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th class="text-center">{{ __('messages.stock_loss.quantity') }}</th>
                        <th class="text-end">{{ __('messages.stock_loss.unit_cost') }}</th>
                        <th class="text-end">{{ __('messages.stock_loss.total') }}</th>
                        <th>{{ __('messages.stock_loss.loss_reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockLoss->items as $item)
                        <tr>
                            <td>{{ $item->product->ean ?? '-' }}</td>
                            <td>{{ is_array($item->product->name) ? ($item->product->name[app()->getLocale()] ?? reset($item->product->name)) : $item->product->name }}</td>
                            <td>{{ $item->product->brand?->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="text-end">${{ number_format($item->total, 2) }}</td>
                            <td>{{ $item->loss_reason ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="3">{{ __('messages.stock_loss.total') }}</th>
                        <th class="text-center">{{ $stockLoss->total_quantity }}</th>
                        <th></th>
                        <th class="text-end">${{ number_format($stockLoss->total_value, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Actions --}}
    <div class="d-flex gap-2 flex-wrap">
        @if($stockLoss->isDraft())
            <a href="{{ route('stock-losses.edit', $stockLoss) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
            </a>

            <form action="{{ route('stock-losses.validate', $stockLoss) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.stock_loss.confirm_validate') }}')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> {{ __('messages.stock_loss.validate_loss') }}
                </button>
            </form>

            <form action="{{ route('stock-losses.destroy', $stockLoss) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.supplier.confirm_delete_return') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                </button>
            </form>
        @endif

        @if($stockLoss->isSupplierRefund() && $stockLoss->status === 'validated')
            <form action="{{ route('stock-losses.request-refund', $stockLoss) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.stock_loss.confirm_request_refund') }}')">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-send"></i> {{ __('messages.stock_loss.request_refund') }}
                </button>
            </form>
        @endif

        @if($stockLoss->isSupplierRefund() && in_array($stockLoss->status, ['validated', 'refund_requested']))
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#confirmRefundModal">
                <i class="bi bi-cash-coin"></i> {{ __('messages.stock_loss.confirm_refund') }}
            </button>
        @endif
    </div>
</div>

{{-- Confirm Refund Modal --}}
@if($stockLoss->isSupplierRefund() && in_array($stockLoss->status, ['validated', 'refund_requested']))
<div class="modal fade" id="confirmRefundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('stock-losses.confirm-refund', $stockLoss) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('messages.stock_loss.confirm_refund') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('messages.stock_loss.enter_refund_amount') }}</p>
                    <p class="text-muted">{{ __('messages.stock_loss.total_value') }}: ${{ number_format($stockLoss->total_value, 2) }}</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('messages.stock_loss.refund_amount') }} ($)</label>
                        <input type="number" name="refund_amount" class="form-control" step="0.00001" min="0.01"
                               value="{{ $stockLoss->total_value }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.stock_loss.confirm_refund') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
