@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Détails de la facture #{{ $invoice->id }}</h1>

    @php
        $totalPaid = $invoice->payments->sum('amount');
        $remaining = max($invoice->total_amount - $totalPaid, 0);
        $productsCount = $invoice->resellerStockDelivery
            ? $invoice->resellerStockDelivery->products->count()
            : ($invoice->salesReport ? $invoice->salesReport->items->count() : 0);
        $paymentsCount = $invoice->payments->count();
    @endphp

    <ul class="nav nav-tabs mb-3" id="invoiceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab">
                Général
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="products-tab" data-bs-toggle="tab" href="#products" role="tab">
                Produits <span class="badge bg-secondary">{{ $productsCount }}</span>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" id="payments-tab" data-bs-toggle="tab" href="#payments" role="tab">
                Paiements <span class="badge bg-secondary">{{ $paymentsCount }}</span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Général -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <table class="table">
                <tr>
                    <th>Nom</th>
                    <td>{{ $invoice->reseller?->name ?? $invoice->store?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td>{{ $invoice->reseller?->type ?? ($invoice->store ? 'store' : '—') }}</td>
                </tr>
                <tr>
                    <th>Date de création</th>
                    <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Montant total</th>
                    <td>${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Total payé</th>
                    <td>${{ number_format($totalPaid, 2) }}</td>
                </tr>
                <tr>
                    <th>Reste à payer</th>
                    <td>${{ number_format($remaining, 2) }}</td>
                </tr>
                @if($invoice->resellerStockDelivery && $invoice->reseller?->type === 'buyer')
                    <tr>
                        <th>Frais de livraison</th>
                        <td>${{ number_format($invoice->resellerStockDelivery->shipping_cost ?? 0, 2) }}</td>
                    </tr>
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
            @if($paymentsCount > 0)
                <!-- Desktop -->
                <div class="d-none d-md-block">
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
                </div>

                <!-- Mobile -->
                <div class="d-md-none">
                    <div class="row">
                        @foreach($invoice->payments as $payment)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <p class="mb-1"><strong>Date :</strong> {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                                        <p class="mb-1"><strong>Montant :</strong> ${{ number_format($payment->amount, 2) }}</p>
                                        <p class="mb-1"><strong>Méthode :</strong> {{ ucfirst($payment->payment_method) }}</p>
                                        <p class="mb-1"><strong>Référence :</strong> {{ $payment->reference }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="alert alert-info">
                    Aucun paiement enregistré pour cette facture.
                </div>
            @endif

            <!-- Bouton pour ouvrir le modal -->
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                Ajouter un paiement
            </button>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un paiement -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller-invoices.addPayment', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Ajouter un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p><strong>Montant total :</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
                        <p><strong>Déjà payé :</strong> ${{ number_format($totalPaid, 2) }}</p>
                        <p><strong>Reste à payer :</strong> ${{ number_format($remaining, 2) }}</p>
                    </div>

                    <div class="mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" 
                               class="form-control" max="{{ $remaining }}" required>
                        <div id="amountWarning" class="text-danger mt-1" style="display:none;">
                            ⚠️ Le montant ne peut pas dépasser ${{ number_format($remaining, 2) }}.
                        </div>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('paymentAmount').addEventListener('input', function() {
    const max = {{ $remaining }};
    const warning = document.getElementById('amountWarning');
    if (parseFloat(this.value) > max) {
        this.value = max;
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
});
</script>
@endpush
@endsection
