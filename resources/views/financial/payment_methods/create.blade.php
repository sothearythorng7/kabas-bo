@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Nouvelle méthode de paiement</h1>
    @include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.payment-methods.store', $store->id) }}" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
        </div>

        <button type="submit" class="btn btn-success">Enregistrer</button>
        <a href="{{ route('financial.payment-methods.index', $store->id) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
