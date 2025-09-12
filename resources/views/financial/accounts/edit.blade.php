@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Modifier compte – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <form method="POST" action="{{ route('financial.accounts.update', [$store->id, $account->id]) }}" class="mt-3">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Code</label>
            <input type="text" name="code" class="form-control" required value="{{ old('code', $account->code) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" name="name" class="form-control" required value="{{ old('name', $account->name) }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="">-- Choisir --</option>
                @foreach(\App\Enums\FinancialAccountType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $account->type->value ?? '') == $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Compte parent</label>
            <select name="parent_id" class="form-select">
                <option value="">-- Aucun --</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $account->parent_id)==$parent->id)>{{ $parent->code }} - {{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Mettre à jour</button>
        <a href="{{ route('financial.accounts.index', $store->id) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
