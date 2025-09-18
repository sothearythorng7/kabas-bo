@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Créer un rapport de ventes pour {{ $supplier->name }}</h1>

    <form action="{{ route('sale-reports.store', $supplier) }}" method="POST">
        @csrf

        {{-- Sélection du Store --}}
        <div class="mb-3">
            <label for="store_id" class="form-label">Store</label>
            <select name="store_id" id="store_id" class="form-select @error('store_id') is-invalid @enderror" required>
                <option value="">-- Sélectionner un store --</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
            @error('store_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Date du rapport --}}
        <div class="mb-3">
            <label for="report_date" class="form-label">Date du rapport</label>
            <input type="date" class="form-control @error('report_date') is-invalid @enderror" name="report_date" id="report_date" value="{{ old('report_date', now()->format('Y-m-d')) }}" required>
            @error('report_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Tableau des produits --}}
        <div class="mb-3">
            <h4>Produits du fournisseur</h4>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>EAN</th>
                        <th>Nom</th>
                        <th>Prix d'achat</th>
                        <th>Quantité vendue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product->ean }}</td>
                            <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                            <td>${{ number_format($product->pivot->purchase_price ?? 0, 2) }}</td>
                            <td>
                                <input type="number" name="products[{{ $product->id }}][quantity_sold]" min="0" value="{{ old('products.' . $product->id . '.quantity_sold', 0) }}" class="form-control form-control-sm" required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @error('products')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        {{-- Boutons --}}
        <div class="mb-3">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-floppy-fill"></i> Enregistrer
            </button>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Annuler
            </a>
        </div>
    </form>
</div>
@endsection
