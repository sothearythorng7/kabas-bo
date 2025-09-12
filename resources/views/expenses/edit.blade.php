@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Ajouter une dépense - {{ $site->name }}</h1>

    <form action="{{ route('stores.expenses.store', $site) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Catégorie</label>
            <select name="category_id" class="form-select" required>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Nom</label>
            <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description">{{ old('description') }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Montant</label>
            <input type="number" step="0.01" class="form-control" name="amount" value="{{ old('amount') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Document</label>
            <input type="file" class="form-control" name="document">
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="{{ route('stores.expenses.index', $site) }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
