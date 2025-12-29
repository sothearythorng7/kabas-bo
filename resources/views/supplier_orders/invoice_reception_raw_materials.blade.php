@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        <i class="bi bi-receipt"></i> {{ __('messages.order.invoice_reception') }} - {{ __('messages.supplier_order.order') }} #{{ $order->id }} - {{ $supplier->name }}
    </h1>
    <p class="text-muted"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials_order') }}</p>

    <form action="{{ route('supplier-orders.storeInvoiceReception', [$supplier, $order]) }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        <div class="table-responsive">
            <table class="table table-striped table-hover mt-3">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.factory.sku') }}</th>
                        <th>{{ __('messages.common.name') }}</th>
                        <th>{{ __('messages.factory.unit') }}</th>
                        <th>{{ __('messages.supplier_order.received_quantity') }}</th>
                        <th>{{ __('messages.factory.expected_price') }}</th>
                        <th>{{ __('messages.supplier_order.price_invoiced') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->rawMaterials as $material)
                    @php
                        $quantityReceived = $material->pivot->quantity_received ?? $material->pivot->quantity_ordered;
                    @endphp
                    <tr>
                        <td>{{ $material->sku ?? '-' }}</td>
                        <td>{{ $material->name }}</td>
                        <td>{{ $material->unit }}</td>
                        <td>{{ number_format($quantityReceived, 2) }}</td>
                        <td>${{ number_format($material->pivot->purchase_price, 2) }}</td>
                        <td>
                            <input type="number" step="0.01" min="0"
                                   name="raw_materials[{{ $material->id }}][price_invoiced]"
                                   value="{{ old('raw_materials.'.$material->id.'.price_invoiced', $material->pivot->purchase_price) }}"
                                   class="form-control form-control-sm price-input"
                                   data-qty="{{ $quantityReceived }}"
                                   style="max-width:100px;"
                                   required>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Upload facture obligatoire --}}
        <div class="card mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="invoice_file" class="form-label fw-bold">{{ __('messages.factory.supplier_invoice') }} (PDF {{ __('messages.factory.or_image') }})</label>
                        <input type="file" class="form-control @error('invoice_file') is-invalid @enderror"
                               id="invoice_file" name="invoice_file" accept="application/pdf,image/*" required>
                        @error('invoice_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">{{ __('messages.factory.invoice_number') }}</label>
                        <input type="text" name="invoice_number" class="form-control"
                               value="{{ old('invoice_number') }}"
                               placeholder="{{ __('messages.factory.optional') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Total facturé dynamique --}}
        <div class="d-flex justify-content-end my-3">
            <h5>
                <span class="fw-bold">{{ __('messages.factory.total_invoiced') }}:</span>
                <span id="total-invoiced">$0.00</span>
            </h5>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-success me-2">
                <i class="bi bi-check2-circle"></i> {{ __('messages.factory.save_invoice_reception') }}
            </button>
            <a href="{{ route('supplier-orders.show', [$supplier, $order]) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.price-input').forEach(input => {
            let qty = parseFloat(input.dataset.qty) || 0;
            let price = parseFloat(input.value) || 0;
            total += qty * price;
        });
        document.getElementById('total-invoiced').innerText = '$' + total.toFixed(2);
    }

    document.querySelectorAll('.price-input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    // Calcul initial au chargement
    calculateTotal();
</script>
@endpush
