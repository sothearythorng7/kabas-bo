@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Coffrets Cadeaux</h1>

    <a href="{{ route('gift-boxes.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Créer un coffret cadeau
    </a>

    <div class="mb-3">
        <form action="{{ route('gift-boxes.index') }}" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Rechercher par nom ou EAN">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Rechercher
                </button>
            </div>
            @if(request('q') || request('brand_id'))
            <div class="col-md-2">
                <a href="{{ route('gift-boxes.index') }}" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </a>
            </div>
            @endif
        </form>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th>EAN</th>
                <th>Nom</th>
                <th style="min-width:220px;">
                    <form action="{{ route('gift-boxes.index') }}" method="GET" id="brandFilterForm">
                        @if(request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif

                        <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Toutes les marques</option>
                            <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                                Sans marque
                            </option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </th>
                <th>Prix</th>
                <th>Prix B2B</th>
                <th>Actif</th>
                <th>Best</th>
                <th class="text-center" style="width:90px;">Photo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($giftBoxes as $giftBox)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('gift-boxes.edit', $giftBox) }}">
                                    <i class="bi bi-pencil-square"></i> Modifier
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('gift-boxes.destroy', $giftBox) }}" method="POST" onsubmit="return confirm('Supprimer ce coffret cadeau ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $giftBox->id }}</td>
                <td>{{ $giftBox->ean }}</td>
                <td>
                    <a href="{{ route('gift-boxes.edit', $giftBox) }}" class="link-primary text-decoration-none">
                        {{ $giftBox->name['fr'] ?? $giftBox->name['en'] ?? 'N/A' }}
                    </a>
                </td>
                <td>{{ $giftBox->brand?->name ?? '-' }}</td>
                <td>{{ number_format($giftBox->price, 2) }} $</td>
                <td>{{ $giftBox->price_btob ? number_format($giftBox->price_btob, 2) . ' $' : '-' }}</td>
                <td>
                    <span class="badge {{ $giftBox->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $giftBox->is_active ? 'Oui' : 'Non' }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $giftBox->is_best_seller ? 'bg-warning' : 'bg-secondary' }}">
                        {{ $giftBox->is_best_seller ? 'Oui' : 'Non' }}
                    </span>
                </td>
                <td class="text-center">
                    @if($giftBox->images_count > 0)
                        <span class="badge bg-info">{{ $giftBox->images_count }}</span>
                    @else
                        <span class="badge bg-secondary">0</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $giftBoxes->links() }}
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
@endsection
