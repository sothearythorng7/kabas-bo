@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Détails de la facture #{{ $invoice->id }}</h1>

    <ul class="nav nav-tabs mb-3" id="invoiceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">Général</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#products" role="tab">Produits</a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="payments-tab" data-bs-toggle="tab" href="#payments" role="tab">Paiements</a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Général -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <table class="table">
                <tr><th>Revendeur</th><td>{{ $invoice->reseller->name }}</td></tr>
                <tr><th>Type</th><td>{{ ucfirst($invoice->reseller->type) }}</td></tr>
                <tr><th>Date de création</th><td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td></tr>
                <tr><th>Montant total</th><td>${{ number_format($invoice->total_amount, 2) }}</td></tr>
                @if($invoice->resellerStockDelivery && $invoice->reseller->type === 'buyer')
                    <tr><th>Frais de livraison</th><td>${{ number_format($invoice->resellerStockDelivery->shipping_cost ?? 0, 2) }}</td></tr>
                @endif
            </table>
        </div>

        <!-- Produits -->
        <div class="tab-pane fade" id="products" role="tabpanel">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prix unitaire</th>
                        <th>Quantité</th>
                    </tr>
                </thead>
                <tbody>
                    @if($invoice->resellerStockDelivery)
                        @foreach($invoice->resellerStockDelivery->products as $product)
                            <tr>
                                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                <td>${{ number_format($product->pivot->unit_price, 2) }}</td>
                                <td>{{ $product->pivot->quantity }}</td>
                            </tr>
                        @endforeach
                    @elseif($invoice->salesReport)
                        @foreach($invoice->salesReport->items as $item)
                            <tr>
                                <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ $item->quantity_sold }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Paiements -->
        <div class="tab-pane fade" id="payments" role="tabpanel">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Référence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method) }}</td>
                            <td>{{ $payment->reference }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h5>Ajouter un paiement</h5>
            <form action="{{ route('reseller-invoices.addPayment', $invoice) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label>Montant</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Méthode</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="transfer">Virement</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Référence</label>
                    <input type="text" name="reference" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer le paiement</button>
            </form>
        </div>
    </div>
</div>
@endsection
