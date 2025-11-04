@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Créer un coffret cadeau</h1>

    <a href="{{ route('gift-boxes.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Retour à la liste
    </a>

    <form action="{{ route('gift-boxes.store') }}" method="POST">
        @csrf

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Informations générales</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">EAN (optionnel)</label>
                        <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror"
                               value="{{ old('ean') }}">
                        @error('ean') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Marque</label>
                        <select name="brand_id" class="form-select">
                            <option value="">--</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id') == $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Prix public *</label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror"
                               value="{{ old('price') }}" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Prix B2B</label>
                        <input type="number" step="0.01" name="price_btob" class="form-control @error('price_btob') is-invalid @enderror"
                               value="{{ old('price_btob') }}">
                        @error('price_btob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Noms par langue --}}
                @php $locales = config('app.website_locales', ['fr', 'en']); @endphp
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $i => $locale)
                        <li class="nav-item">
                            <button class="nav-link {{ $i == 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#name-{{ $locale }}" type="button" role="tab">
                                {{ strtoupper($locale) }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content border border-top-0 p-3 mb-3">
                    @foreach($locales as $i => $locale)
                        <div class="tab-pane fade {{ $i == 0 ? 'show active' : '' }}" id="name-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Nom ({{ strtoupper($locale) }}) *</label>
                                <input type="text" name="name[{{ $locale }}]" class="form-control @error("name.{$locale}") is-invalid @enderror"
                                       value="{{ old("name.{$locale}") }}" required>
                                @error("name.{$locale}") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description ({{ strtoupper($locale) }})</label>
                                <textarea name="description[{{ $locale }}]" class="form-control" rows="4">{{ old("description.{$locale}") }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Actif</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_best_seller" id="is_best_seller" value="1" @checked(old('is_best_seller'))>
                            <label class="form-check-label" for="is_best_seller">Best Seller</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-save"></i> Créer le coffret cadeau
        </button>
        <p class="text-muted mt-2">
            <small>Vous pourrez ajouter des catégories, produits et photos après la création du coffret.</small>
        </p>
    </form>
</div>

@endsection
