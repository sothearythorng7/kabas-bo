@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t("Nouveau compte") – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.accounts.store', $store->id) }}" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">@t("Code")</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Nom")</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">@t("type")</label>
            <select name="type" class="form-select" required>
                <option value="">-- @t("Choisir") --</option>
                @foreach(\App\Enums\FinancialAccountType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $account->type->value ?? '') == $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">@t("Compte parent")</label>
            <select name="parent_id" class="form-select">
                <option value="">-- @t("Aucun") --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id')==$parent->id)>{{ $parent->code }} - {{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Enregistrer</button>
        <a href="{{ route('financial.accounts.index', $store->id) }}" class="btn btn-secondary">@t("Annuler")</a>
    </form>
</div>
@endsection
