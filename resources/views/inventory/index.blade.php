@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Gestion d'Inventaire</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-download"></i> 1. Télécharger le fichier d'inventaire</h5>
                </div>
                <div class="card-body">
                    <p>Téléchargez un fichier Excel contenant tous les produits avec leur stock théorique actuel.</p>
                    <form action="{{ route('inventory.export') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="export_store_id" class="form-label">Sélectionner le site *</label>
                            <select name="store_id" id="export_store_id" class="form-select" required>
                                <option value="">-- Choisir un site --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-file-earmark-excel"></i> Télécharger le fichier Excel
                        </button>
                    </form>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Le fichier Excel sera protégé. Vous pourrez uniquement modifier la colonne "Stock Réel".
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-upload"></i> 2. Importer le fichier complété</h5>
                </div>
                <div class="card-body">
                    <p>Après avoir complété les quantités réelles, importez le fichier pour mettre à jour les stocks.</p>
                    <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="import_store_id" class="form-label">Sélectionner le site *</label>
                            <select name="store_id" id="import_store_id" class="form-select" required>
                                <option value="">-- Choisir un site --</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="inventory_file" class="form-label">Fichier Excel *</label>
                            <input type="file" name="inventory_file" id="inventory_file" class="form-control" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cloud-upload"></i> Importer et analyser
                        </button>
                    </form>
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Attention:</strong> Assurez-vous de sélectionner le même site que lors du téléchargement.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Procédure d'inventaire</h5>
        </div>
        <div class="card-body">
            <ol>
                <li><strong>Téléchargement:</strong> Sélectionnez un site et téléchargez le fichier Excel.</li>
                <li><strong>Comptage:</strong> Imprimez le fichier et comptez physiquement les stocks dans votre magasin.</li>
                <li><strong>Saisie:</strong> Ouvrez le fichier Excel et remplissez la colonne "Stock Réel (à compléter)" avec les quantités réelles comptées.</li>
                <li><strong>Import:</strong> Importez le fichier complété. Le système analysera les différences.</li>
                <li><strong>Confirmation:</strong> Vérifiez les ajustements proposés et confirmez pour mettre à jour les stocks.</li>
            </ol>
            <div class="alert alert-primary">
                <i class="bi bi-lightbulb"></i>
                <strong>Astuce:</strong> La colonne "Différence" calcule automatiquement l'écart (Stock Réel - Stock Théorique). Les ajustements seront appliqués aux batches de stock existants.
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Succès</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-danger text-white">
                <strong class="me-auto">Erreur</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('error') }}
            </div>
        </div>
    </div>
@endif

@if(session('info'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-info text-white">
                <strong class="me-auto">Information</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('info') }}
            </div>
        </div>
    </div>
@endif
@endsection
