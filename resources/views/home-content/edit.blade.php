@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Contenu de la page d'accueil</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Texte de présentation</h5>
            <small class="text-muted">Ce texte apparaît sur la page d'accueil du site public, entre le carrousel et la section "Our Best Seller"</small>
        </div>
        <div class="card-body">
            <form action="{{ route('home-content.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="presentation_text_fr" class="form-label">
                            <i class="bi bi-flag"></i> Texte en Français <span class="text-danger">*</span>
                        </label>
                        <textarea
                            name="presentation_text_fr"
                            id="presentation_text_fr"
                            rows="5"
                            class="form-control @error('presentation_text_fr') is-invalid @enderror"
                            required>{{ old('presentation_text_fr', $presentationText->value['fr'] ?? '') }}</textarea>
                        @error('presentation_text_fr')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maximum 1000 caractères</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="presentation_text_en" class="form-label">
                            <i class="bi bi-flag"></i> Text in English <span class="text-danger">*</span>
                        </label>
                        <textarea
                            name="presentation_text_en"
                            id="presentation_text_en"
                            rows="5"
                            class="form-control @error('presentation_text_en') is-invalid @enderror"
                            required>{{ old('presentation_text_en', $presentationText->value['en'] ?? '') }}</textarea>
                        @error('presentation_text_en')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Maximum 1000 characters</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i>
        <strong>Astuce :</strong> Ce texte sera affiché en gras et centré sur la page d'accueil.
        Gardez-le concis et percutant pour capter l'attention des visiteurs.
    </div>
</div>
@endsection
