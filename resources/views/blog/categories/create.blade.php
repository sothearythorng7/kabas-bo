@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Créer une Catégorie</h1>

    <form action="{{ route('blog.categories.store') }}" method="POST">
        @csrf

        {{-- Onglets par locale --}}
        <ul class="nav nav-tabs" id="categoryLocalesTab" role="tablist">
            @foreach($locales as $index => $locale)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($index===0) active @endif"
                            id="{{ $locale }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#{{ $locale }}"
                            type="button" role="tab">
                        {{ strtoupper($locale) }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content border border-top-0 p-3 mb-3">
            @foreach($locales as $index => $locale)
                <div class="tab-pane fade @if($index===0) show active @endif" id="{{ $locale }}" role="tabpanel">
                    {{-- Nom --}}
                    <div class="mb-3">
                        <label class="form-label">Nom ({{ strtoupper($locale) }}) <span class="text-danger">*</span></label>
                        <input type="text" name="name[{{ $locale }}]" class="form-control @error('name.'.$locale) is-invalid @enderror" value="{{ old('name.'.$locale) }}" required>
                        @error('name.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label class="form-label">Description ({{ strtoupper($locale) }})</label>
                        <textarea name="description[{{ $locale }}]" class="form-control @error('description.'.$locale) is-invalid @enderror" rows="3">{{ old('description.'.$locale) }}</textarea>
                        @error('description.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Ordre --}}
        <div class="mb-3">
            <label class="form-label">Ordre d'affichage</label>
            <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', 0) }}" min="0">
            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Statut --}}
        <div class="mb-3">
            <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Catégorie active
                </label>
            </div>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Enregistrer
        </button>
        <a href="{{ route('blog.categories.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
</div>
@endsection
