@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">
        Réception de facture - Commande #{{ $order->id }} - {{ $supplier->name }}
    </h1>

    <form action="{{ route('supplier-orders.storeInvoiceReception', [$supplier, $order]) }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        <table class="table table-striped table-hover mt-3">
            <thead class="table-light">
                <tr>
                    <th>Produit</th>
                    <th>Quantité reçue</th>
                    <th>Prix attendu</th>
                    <th>Prix facturé</th>
                    <th>Mettre à jour prix référence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->products as $product)
                <tr>
                    <td>
                        {{ $product->name[app()->getLocale()] ?? reset($product->name) }}
                        @if($product->brand) ({{ $product->brand->name }}) @endif
                    </td>
                    <td>{{ $product->pivot->quantity_received ?? $product->pivot->quantity_ordered }}</td>
                    <td>{{ number_format($product->pivot->purchase_price, 2) }} $</td>
                    <td>
                        <input type="number" step="0.01"
                               name="products[{{ $product->id }}][price_invoiced]"
                               value="{{ old('products.'.$product->id.'.price_invoiced', $product->pivot->purchase_price) }}"
                               class="form-control form-control-sm price-input"
                               data-qty="{{ $product->pivot->quantity_received ?? $product->pivot->quantity_ordered }}"
                               required>
                    </td>
                    <td class="text-center">
                        <input type="checkbox" name="update_reference_price[{{ $product->id }}]" value="1">
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
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
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

    // Calcul initial au chargement
    calculateTotal();
</script>
@endpush
