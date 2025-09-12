@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Ajouter une catégorie</h1>

    <form action="{{ route('stores.expense-categories.store', $site) }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="{{ route('stores.expense-categories.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
