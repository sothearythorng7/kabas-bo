@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">@t('Shift en cours') – {{ $store->name }}</h1>

    @include('financial.layouts.nav')

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> @t('Filtres')</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('financial.shifts.index', $store->id) }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">@t('Date de début')</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">@t('Date de fin')</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">@t('Utilisateur')</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- @t('Tous les utilisateurs') --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> @t('Filtrer')
                    </button>
                    <a href="{{ route('financial.shifts.index', $store->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> @t('Réinitialiser')
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if(!$shift)
        <div class="alert alert-info">@t('Aucun shift ouvert pour ce magasin.')</div>
    @else
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">@t('Nombre de ventes')</h5>
                        <p class="card-text fs-3">{{ $shiftStats['number_of_sales'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">@t('Montant total des ventes')</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_sales'], 2) }} $</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">@t("Nombre total d'articles vendus")</h5>
                        <p class="card-text fs-3">{{ $shiftStats['total_items'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">@t('Total des discounts')</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_discounts'], 2) }} $</p>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>@t('ID Vente')</th>
                    <th>@t("Nombre d'articles")</th>
                    <th>@t('Total encaissé')</th>
                    <th>@t('Total discounts')</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sales as $sale)
                @php
                    $totalDiscounts = 0;

                    foreach ($sale->items as $item) {
                        foreach ($item->discounts ?? [] as $d) {
                            if ($d['type'] === 'amount') {
                                $totalDiscounts += $d['value'] * $item->quantity; // montant fixe par article
                            } elseif ($d['type'] === 'percent') {
                                $totalDiscounts += ($d['value'] / 100) * $item->price * $item->quantity;
                            }
                        }
                    }

                    foreach ($sale->discounts ?? [] as $d) {
                        if ($d['type'] === 'amount') {
                            $totalDiscounts += $d['value']; // montant fixe sur la vente
                        } elseif ($d['type'] === 'percent') {
                            $totalDiscounts += ($d['value'] / 100) * $sale->total; // pourcentage sur le total de la vente
                        }
                    }

                @endphp
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#itemsModal{{ $sale->id }}">
                            {{ $sale->items->sum('quantity') }}
                        </a>
                    </td>
                    <td>{{ number_format($sale->total, 2) }} $</td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#discountModal{{ $sale->id }}">
                            {{ number_format($totalDiscounts, 2) }} $
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Modales en dehors de la table -->
        @foreach($sales as $sale)
            <!-- Modal Items -->
            <div class="modal fade" id="itemsModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@t('Articles de la vente') #{{ $sale->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>@t('Nom')</th>
                                        <th>@t('EAN')</th>
                                        <th>@t('Prix unitaire')</th>
                                        <th>@t('Quantité')</th>
                                        <th>@t('Discount')</th>
                                        <th>@t('Total')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($sale->items as $item)
                                    @php
                                        $itemDiscounts = collect($item->discounts ?? [])->sum('amount');
                                        $unitPrice = $item->unit_price ?? 0;
                                        $quantity = $item->quantity ?? 1;
                                        $lineTotal = ($unitPrice * $quantity) - $itemDiscounts;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($item->product)
                                                {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                                            @elseif($item->is_delivery)
                                                @t('Service de livraison')
                                                @if($item->delivery_address)
                                                    <br><small class="text-muted">{{ $item->delivery_address }}</small>
                                                @endif
                                            @else
                                                @t('Article inconnu')
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->product && $item->product->ean)
                                                {{ $item->product->ean }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>${{ number_format($unitPrice, 2) }}</td>
                                        <td>{{ $quantity }}</td>
                                        <td>${{ number_format($itemDiscounts, 2) }}</td>
                                        <td><strong>${{ number_format($lineTotal, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Discounts -->
            <div class="modal fade" id="discountModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@t('Discounts de la vente') #{{ $sale->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <ul>
                                @foreach($sale->items as $item)
                                    @foreach($item->discounts ?? [] as $d)
                                        <li>
                                            @if($item->product)
                                                {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                                            @elseif($item->is_delivery)
                                                @t('Service de livraison')
                                            @else
                                                @t('Article inconnu')
                                            @endif
                                            : {{ $d['amount'] ?? 0 }} $
                                        </li>
                                    @endforeach
                                @endforeach
                                @foreach($sale->discounts ?? [] as $d)
                                    <li>@t('Global') : {{ $d['amount'] ?? 0 }} $</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
