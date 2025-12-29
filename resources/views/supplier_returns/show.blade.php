@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">
            {{ __('messages.supplier_return.show_title') }} #{{ $return->id }}
            @if($return->isPending())
                <span class="badge bg-warning">{{ __('messages.supplier_return.status_pending') }}</span>
            @elseif($return->isDraft())
                <span class="badge bg-secondary">{{ __('messages.supplier.draft') }}</span>
            @else
                <span class="badge bg-success">{{ __('messages.supplier.validated') }}</span>
            @endif
        </h1>
        <a href="{{ route('suppliers.edit', ['supplier' => $supplier, '#returns']) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.supplier_return.details') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.supplier.supplier') }}:</strong> {{ $supplier->name }}</p>
                    <p><strong>{{ __('messages.supplier.store_name') }}:</strong> {{ $return->store->name }}</p>
                    <p><strong>{{ __('messages.common.date') }}:</strong> {{ $return->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>{{ __('messages.supplier_return.created_by') }}:</strong> {{ $return->createdBy?->name ?? '-' }}</p>
                    @if($return->notes)
                        <p><strong>{{ __('messages.supplier_return.notes') }}:</strong> {{ $return->notes }}</p>
                    @endif
                    @if($return->isValidated())
                        <p><strong>{{ __('messages.supplier_return.validated_at') }}:</strong> {{ $return->validated_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <strong>{{ __('messages.supplier_return.summary') }}</strong>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('messages.supplier_return.total_products') }}:</strong> {{ $return->items->count() }}</p>
                    <p><strong>{{ __('messages.supplier_return.total_quantity') }}:</strong> {{ $return->total_quantity }}</p>
                    <p><strong>{{ __('messages.supplier_return.total_value') }}:</strong> ${{ number_format($return->total_value, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <strong>{{ __('messages.supplier_return.products') }}</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand_label') }}</th>
                        <th class="text-center">{{ __('messages.supplier_return.quantity') }}</th>
                        <th class="text-end">{{ __('messages.supplier.price') }}</th>
                        <th class="text-end">{{ __('messages.supplier_return.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $item)
                        <tr>
                            <td>{{ $item->product->ean ?? '-' }}</td>
                            <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                            <td>{{ $item->product->brand?->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">${{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="3">{{ __('messages.supplier_return.total') }}</th>
                        <th class="text-center">{{ $return->total_quantity }}</th>
                        <th></th>
                        <th class="text-end">${{ number_format($return->total_value, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if($return->isPending())
            <a href="{{ route('supplier-returns.edit', [$supplier, $return]) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
            </a>

            <form action="{{ route('supplier-returns.validate', [$supplier, $return]) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.supplier_return.confirm_validate') }}')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> {{ __('messages.supplier_return.validate_return') }}
                </button>
            </form>

            <form action="{{ route('supplier-returns.destroy', [$supplier, $return]) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.supplier.confirm_delete_return') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                </button>
            </form>
        @elseif($return->isDraft())
            <form action="{{ route('supplier-returns.validate', [$supplier, $return]) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.supplier_return.confirm_validate') }}')">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> {{ __('messages.supplier_return.validate_and_deduct_stock') }}
                </button>
            </form>

            <form action="{{ route('supplier-returns.destroy', [$supplier, $return]) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.supplier.confirm_delete_return') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                </button>
            </form>
        @else
            <a href="{{ route('supplier-returns.pdf', [$supplier, $return]) }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf"></i> {{ __('messages.supplier_return.download_pdf') }}
            </a>
        @endif
    </div>
</div>
@endsection
