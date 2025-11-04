@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Confirmation des Ajustements d'Inventaire</h1>

    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Attention:</strong> Les modifications suivantes seront appliquées aux stocks du site <strong>{{ $store->name }}</strong>.
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Ajustements à effectuer ({{ count($updates) }} produit(s))</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th class="text-center">Stock Théorique</th>
                            <th class="text-center">Stock Réel</th>
                            <th class="text-center">Différence</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($updates as $update)
                            <tr>
                                <td>{{ $update['product_id'] }}</td>
                                <td>{{ $update['product_name'] }}</td>
                                <td class="text-center">{{ $update['theoretical'] }}</td>
                                <td class="text-center"><strong>{{ $update['real'] }}</strong></td>
                                <td class="text-center">
                                    @if($update['difference'] > 0)
                                        <span class="badge bg-success">+{{ $update['difference'] }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $update['difference'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($update['difference'] > 0)
                                        <span class="text-success"><i class="bi bi-arrow-up-circle"></i> Ajout de stock</span>
                                    @else
                                        <span class="text-danger"><i class="bi bi-arrow-down-circle"></i> Retrait de stock</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <form action="{{ route('inventory.apply') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir appliquer ces ajustements aux stocks ?');">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> Confirmer et Appliquer les Ajustements
            </button>
        </form>

        <form action="{{ route('inventory.cancel') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-circle"></i> Annuler
            </button>
        </form>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i>
        <strong>Information:</strong>
        <ul class="mb-0 mt-2">
            <li>Les ajouts de stock créeront de nouveaux batches avec un prix unitaire de 0$ (ajustement d'inventaire).</li>
            <li>Les retraits de stock seront déduits des batches existants selon la méthode FIFO (First In, First Out).</li>
            <li>Cette opération ne peut pas être annulée une fois appliquée.</li>
        </ul>
    </div>
</div>
@endsection
