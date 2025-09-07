@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.stocks.title') }}</h1>

    <!-- Formulaire de recherche -->
    <div class="mb-3">
        <form action="{{ route('stocks.index') }}" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" 
                       placeholder="Rechercher par nom ou EAN">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Rechercher
                </button>
            </div>
            @if(request('q'))
            <div class="col-md-2">
                <a href="{{ route('stocks.index') }}" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> Réinitialiser
                </a>
            </div>
            @endif
        </form>
    </div>

    <!-- Version Desktop -->
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>{{ __('messages.product.name') }}</th>
                    @foreach($shops as $shop)
                        <th>{{ $shop->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                    <tr>
                        <td>{{ $p->ean }}</td>
                        <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>

                        @foreach($shops as $shop)
                            @php
                                // Stock total par lot
                                $lot = ($stocks[$p->id] ?? collect())->firstWhere('store_id', $shop->id);
                                $stock = $lot->stock_quantity ?? 0;

                                // Stock d'alerte depuis pivot
                                $alert = ($pivotAlerts[$p->id][$shop->id] ?? 0);

                                $isOk = $stock >= $alert;
                            @endphp
                            <td>
                                <span class="badge {{ $isOk ? 'bg-success' : 'bg-danger' }}">
                                    {{ $stock }} / {{ $alert }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $products->links() }}
    </div>

    <!-- Version Mobile -->
    <div class="d-md-none">
        <div class="row">
            @foreach($products as $p)
            <div class="col-12 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-1">
                            {{ $p->name[app()->getLocale()] ?? reset($p->name) }}
                        </h5>
                        <p class="mb-1"><strong>EAN:</strong> {{ $p->ean }}</p>
                        <ul class="list-group list-group-flush">
                            @foreach($shops as $shop)
                                @php
                                    $lot = ($stocks[$p->id] ?? collect())->firstWhere('store_id', $shop->id);
                                    $stock = $lot->stock_quantity ?? 0;

                                    $alert = ($pivotAlerts[$p->id][$shop->id] ?? 0);
                                    $isOk = $stock >= $alert;
                                @endphp
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $shop->name }}</span>
                                    <span class="badge {{ $isOk ? 'bg-success' : 'bg-danger' }}">
                                        {{ $stock }} / {{ $alert }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endforeach

            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection
