@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Ajouter un paiement fournisseur - {{ $site->name }}</h1>

    <form action="{{ route('stores.payments.store', $site) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Fournisseur</label>
            <input type="text" class="form-control" name="supplier_name" value="{{ old('supplier_name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Référence</label>
            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Date échéance</label>
            <input type="date" class="form-control" name="due_date" value="{{ old('due_date') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Document</label>
            <input type="file" class="form-control" name="document">
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="{{ route('stores.payments.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
