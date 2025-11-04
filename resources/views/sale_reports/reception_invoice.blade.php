@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        Réception de facture - Rapport #{{ $saleReport->id }} - {{ $supplier->name }}
    </h1>

    <form action="{{ route('sale-reports.storeInvoiceReception', [$supplier, $saleReport]) }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        <table class="table table-striped table-hover mt-3">
            <thead class="table-light">
                <tr>
                    <th>Produit</th>
                    <th>Quantité vendue</th>
                    <th>Prix attendu</th>
                    <th>Prix facturé</th>
                    <th>Mettre à jour prix référence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($saleReport->items as $item)
                <tr>
                    <td>
                        {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                        @if($item->product->brand) ({{ $item->product->brand->name }}) @endif
                    </td>
                    <td>{{ $item->quantity_sold }}</td>
                    <td>{{ number_format($item->unit_price, 2) }} $</td>
                    <td>
                        <input type="number" step="0.01"
                               name="products[{{ $item->product_id }}][price_invoiced]"
                               value="{{ old('products.'.$item->product_id.'.price_invoiced', $item->unit_price) }}"
                               class="form-control form-control-sm price-input"
                               data-qty="{{ $item->quantity_sold }}"
                               required>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="update_reference_price[{{ $item->product_id }}]" value="1">
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Upload facture obligatoire --}}
        <div class="mb-3">
            <label for="invoice_file" class="form-label fw-bold">Facture fournisseur (PDF ou image)</label>
            <input type="file" class="form-control @error('invoice_file') is-invalid @enderror"
                   id="invoice_file" name="invoice_file" accept="application/pdf,image/*" required>
            @error('invoice_file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Total facturé dynamique --}}
        <div class="d-flex justify-content-end my-3">
            <h5>
                <span class="fw-bold">Total facturé :</span>
                <span id="total-invoiced">0,00 $</span>
            </h5>
        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-success me-2">
                <i class="bi bi-check2-circle"></i> Enregistrer la réception de facture
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}#sales-reports" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Annuler
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
        document.getElementById('total-invoiced').innerText =
            total.toFixed(2).replace('.', ',') + ' $';
    }

    document.querySelectorAll('.price-input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    calculateTotal();
</script>
@endpush
