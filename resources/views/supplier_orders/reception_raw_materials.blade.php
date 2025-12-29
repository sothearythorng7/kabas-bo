@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-box-seam"></i> {{ __('messages.order.reception') }} #{{ $order->id }} - {{ $supplier->name }}</h1>
    <p class="text-muted"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials_order') }}</p>

    <form action="{{ route('supplier-orders.storeReception', [$supplier, $order]) }}" method="POST">
        @csrf

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.factory.sku') }}</th>
                        <th>{{ __('messages.common.name') }}</th>
                        <th>{{ __('messages.factory.unit') }}</th>
                        <th>{{ __('messages.factory.current_stock') }}</th>
                        <th>{{ __('messages.factory.qty_ordered') }}</th>
                        <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                        <th>{{ __('messages.factory.batch_number_field') }}</th>
                        <th>{{ __('messages.factory.expiry_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->rawMaterials as $material)
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
                        <td>{{ number_format($material->pivot->quantity_ordered, 2) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0"
                                   name="raw_materials[{{ $material->id }}][quantity_received]"
                                   value="{{ $material->pivot->quantity_ordered }}"
                                   class="form-control form-control-sm" style="max-width:100px;">
                        </td>
                        <td>
                            <input type="text"
                                   name="raw_materials[{{ $material->id }}][batch_number]"
                                   class="form-control form-control-sm"
                                   style="max-width:120px;"
                                   placeholder="{{ __('messages.factory.optional') }}">
                        </td>
                        <td>
                            <input type="date"
                                   name="raw_materials[{{ $material->id }}][expires_at]"
                                   class="form-control form-control-sm"
                                   style="max-width:150px;">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.factory.reception_notes') }}</label>
                        <textarea name="reception_notes" class="form-control" rows="2" placeholder="{{ __('messages.factory.optional_notes_reception') }}"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-check2-circle"></i> {{ __('messages.factory.validate_reception') }}
            </button>
            <a href="{{ route('supplier-orders.show', [$supplier, $order]) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
