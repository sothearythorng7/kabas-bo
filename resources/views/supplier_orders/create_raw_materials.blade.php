@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-cart-plus"></i> {{ __('messages.supplier_order.create_title') }} - {{ $supplier->name }}</h1>
    <p class="text-muted"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials_order') }}</p>

    <form action="{{ route('supplier-orders.store', $supplier) }}" method="POST">
        @csrf

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.factory.sku') }}</th>
                        <th>{{ __('messages.common.name') }}</th>
                        <th>{{ __('messages.factory.unit') }}</th>
                        <th>{{ __('messages.factory.current_stock') }}</th>
                        <th>{{ __('messages.factory.purchase_price') }}</th>
                        <th>{{ __('messages.supplier_order.quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rawMaterials as $material)
                    <tr>
                        <td>{{ $material->sku ?? '-' }}</td>
                        <td>{{ $material->name }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>
                            @if($material->track_stock)
                                <span class="badge {{ $material->isLowStock() ? 'bg-danger' : 'bg-success' }}">
                                    {{ number_format($material->total_stock, 2) }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0"
                                   name="raw_materials[{{ $material->id }}][purchase_price]"
                                   value="0"
                                   class="form-control form-control-sm"
                                   style="max-width:100px;">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0"
                                   name="raw_materials[{{ $material->id }}][quantity]"
                                   value="0"
                                   class="form-control form-control-sm"
                                   style="max-width:100px;">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            {{ __('messages.factory.no_raw_materials') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rawMaterials->isNotEmpty())
        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier_order.create') }}
            </button>
            <a href="{{ route('factory.suppliers.edit', $supplier) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
        @endif
    </form>
</div>
@endsection
