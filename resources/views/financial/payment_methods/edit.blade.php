@extends('layouts.app')

@section('content')
<div class="container mt-4">
<h1 class="crud_title">@t("Modifier méthode de paiement")</h1>
@include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.payment-methods.update', [$store->id, $paymentMethod->id]) }}" class="mt-3">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">@t("Nom")</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $paymentMethod->name) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Code")</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code', $paymentMethod->code) }}">
        </div>

        <button type="submit" class="btn btn-success">@t("Mettre à jour")</button>
        <a href="{{ route('financial.payment-methods.index', $store->id) }}" class="btn btn-secondary">@t("Annuler")</a>
    </form>
</div>
@endsection
