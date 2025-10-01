@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">Shift en cours – {{ $store->name }}</h1>

    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if(!$shift)
        <div class="alert alert-info">Aucun shift ouvert pour ce magasin.</div>
    @else
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Nombre de ventes</h5>
                        <p class="card-text fs-3">{{ $shiftStats['number_of_sales'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Montant total des ventes</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_sales'], 2) }} €</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Nombre total d'articles vendus</h5>
                        <p class="card-text fs-3">{{ $shiftStats['total_items'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total des discounts</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_discounts'], 2) }} €</p>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID Vente</th>
                    <th>Nombre d'articles</th>
                    <th>Total encaissé</th>
                    <th>Total discounts</th>
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
                    <td>{{ number_format($sale->total, 2) }} €</td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#discountModal{{ $sale->id }}">
                            {{ number_format($totalDiscounts, 2) }} €
                        </a>
                    </td>
                </tr>

                <!-- Modal Items -->
                <div class="modal fade" id="itemsModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Articles de la vente #{{ $sale->id }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <ul>
                                @foreach($sale->items as $item)
                                    <li>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</li>
                                @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Discounts -->
                <div class="modal fade" id="discountModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Discounts de la vente #{{ $sale->id }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <ul>
                                    @foreach($sale->items as $item)
                                        @foreach($item->discounts ?? [] as $d)
                                            <li>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }} : {{ $d['amount'] ?? 0 }} €</li>
                                        @endforeach
                                    @endforeach
                                    @foreach($sale->discounts ?? [] as $d)
                                        <li>Global : {{ $d['amount'] ?? 0 }} €</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
