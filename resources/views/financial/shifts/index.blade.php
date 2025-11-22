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

    @if($shifts->isNotEmpty())
        <!-- Liste des shifts filtrés -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list"></i> @t('Liste des shifts')</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>@t('ID')</th>
                            <th>@t('Utilisateur')</th>
                            <th>@t('Début')</th>
                            <th>@t('Fin')</th>
                            <th>@t('Durée')</th>
                            <th>@t('Caisse ouverture')</th>
                            <th>@t('Caisse clôture')</th>
                            <th>@t('Différence cash')</th>
                            <th>@t('Actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>{{ $s->user->name ?? 'N/A' }}</td>
                            <td>{{ $s->started_at ? $s->started_at->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td>{{ $s->ended_at ? $s->ended_at->format('d/m/Y H:i') : 'En cours' }}</td>
                            <td>
                                @if($s->started_at && $s->ended_at)
                                    {{ $s->started_at->diffForHumans($s->ended_at, true) }}
                                @else
                                    {{ $s->started_at->diffForHumans(null, true) }}
                                @endif
                            </td>
                            <td>${{ number_format($s->opening_cash ?? 0, 2) }}</td>
                            <td>
                                @if($s->closing_cash !== null)
                                    ${{ number_format($s->closing_cash, 2) }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($s->cash_difference !== null)
                                    <span class="badge bg-{{ $s->cash_difference == 0 ? 'success' : ($s->cash_difference > 0 ? 'warning' : 'danger') }}">
                                        {{ $s->cash_difference > 0 ? '+' : '' }}${{ number_format($s->cash_difference, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('financial.shifts.index', array_merge(['store' => $store->id, 'shift_id' => $s->id], request()->only(['date_from', 'date_to', 'user_id']))) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> @t('Voir détails')
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(!$shift)
        <div class="alert alert-info">@t('Aucun shift ouvert pour ce magasin.')</div>
    @else
        <!-- Informations du shift -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> @t('Détails du shift')</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>@t('Utilisateur'):</strong><br>
                        {{ $shift->user->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Début'):</strong><br>
                        {{ $shift->started_at ? $shift->started_at->format('d/m/Y H:i') : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Fin'):</strong><br>
                        {{ $shift->ended_at ? $shift->ended_at->format('d/m/Y H:i') : 'En cours' }}
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Durée'):</strong><br>
                        @if($shift->started_at && $shift->ended_at)
                            {{ $shift->started_at->diffForHumans($shift->ended_at, true) }}
                        @else
                            {{ $shift->started_at->diffForHumans(null, true) }}
                        @endif
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>@t('Caisse ouverture'):</strong><br>
                        ${{ number_format($shift->opening_cash ?? 0, 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Caisse clôture'):</strong><br>
                        @if($shift->closing_cash !== null)
                            ${{ number_format($shift->closing_cash, 2) }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Différence cash'):</strong><br>
                        @if($shift->cash_difference !== null)
                            <span class="badge bg-{{ $shift->cash_difference == 0 ? 'success' : ($shift->cash_difference > 0 ? 'warning' : 'danger') }}">
                                {{ $shift->cash_difference > 0 ? '+' : '' }}${{ number_format($shift->cash_difference, 2) }}
                            </span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>@t('Visiteurs'):</strong><br>
                        {{ $shift->visitors_count ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

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
