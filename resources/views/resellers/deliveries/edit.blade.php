@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.resellers.deliveries') }} #{{ $delivery->id }} - {{ __('messages.btn.edit') }}</h1>
    @php
        $resellerType = $delivery->getResellerType();
        $productsCount = $delivery->products->count();
        if($resellerType == 'buyer')
        {
            $totalPaid = $delivery->invoice?->payments->sum('amount') ?? 0;
            $remaining = max(($delivery->invoice?->total_amount ?? 0) - $totalPaid, 0);
            $paymentsCount = $delivery->invoice?->payments->count() ?? 0;

            // Statut de paiement
            if(!$delivery->invoice) {
                $paymentStatus = 'N/A';
            } elseif($remaining <= 0) {
                $paymentStatus = 'paid';
            } elseif($totalPaid > 0) {
                $paymentStatus = 'partially_paid';
            } else {
                $paymentStatus = 'unpaid';
            }            
        }

    @endphp

    {{-- Onglets --}}
    <ul class="nav nav-tabs" id="deliveryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                {{ __('messages.resellers.tab_general') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="false">
                {{ __('messages.product.products') }} <span class="badge bg-secondary">{{ $productsCount }}</span>
            </button>
        </li>
        @if($resellerType == 'buyer')
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                Paiements <span class="badge bg-secondary">{{ $paymentsCount }}</span>
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content mt-3" id="deliveryTabsContent">
        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            
                {{-- Infos paiement --}}
                @if($delivery->invoice && $resellerType == 'buyer')
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Montant total à payer</h6>
                                <p class="card-text">${{ number_format($delivery->invoice->total_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-info">
                            <div class="card-body">
                                <h6 class="card-title">Montant total déjà payé</h6>
                                <p class="card-text">${{ number_format($totalPaid, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-warning">
                            <div class="card-body">
                                <h6 class="card-title">Montant restant à payer</h6>
                                <p class="card-text">${{ number_format($remaining, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <div class="card text-center bg-secondary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Statut de paiement</h6>
                                <p class="card-text">
                                    @if($paymentStatus === 'paid')
                                        <span class="badge bg-success">Payé</span>
                                    @elseif($paymentStatus === 'partially_paid')
                                        <span class="badge bg-warning text-dark">Partiellement payé</span>
                                    @elseif($paymentStatus === 'unpaid')
                                        <span class="badge bg-danger">Non payé</span>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            <form action="{{ route('reseller-stock-deliveries.update', [$reseller->id, $delivery->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="status" class="form-label">{{ __('messages.resellers.status') }}</label>
                    <select name="status" id="status" class="form-select">
                        @foreach(\App\Models\ResellerStockDelivery::STATUS_OPTIONS as $key => $label)
                            <option value="{{ $key }}" @selected($delivery->status === $key)>{{ __('messages.order.' . strtolower($key)) ?? $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="shipping_cost" class="form-label">{{ __('messages.resellers.shipping_cost') }}</label>
                    <input type="number" step="0.01" name="shipping_cost" id="shipping_cost" class="form-control" value="{{ old('shipping_cost', $delivery->shipping_cost) }}">
                    <small class="text-muted">{{ __('messages.resellers.edit_only_after_creation') ?? 'Only editable after creation' }}</small>
                </div>

                <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                <a href="{{ route('resellers.show', $reseller->id) }}" class="btn btn-secondary">
                    {{ __('messages.btn.cancel') }}
                </a>
            </form>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="products" role="tabpanel" aria-labelledby="products-tab">
            <h3>{{ __('messages.product.products') }} {{ __('messages.resellers.in_delivery') ?? 'in this Delivery' }}</h3>

            <!-- Desktop -->
            <div class="d-none d-md-block">
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>{{ __('messages.stock_value.ean') }}</th>
                            <th>{{ __('messages.product.name') }}</th>
                            <th>{{ __('messages.product.brand') }}</th>
                            <th>{{ __('messages.resellers.quantity') }}</th>
                            <th>{{ __('messages.product.price_btob') }}</th>
                            <th>{{ __('messages.resellers.total_value') ?? 'Total (€)' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($delivery->products as $product)
                            <tr>
                                <td>{{ $product->ean }}</td>
                                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                                <td>{{ $product->brand?->name ?? '-' }}</td>
                                <td>{{ $product->pivot->quantity }}</td>
                                <td>{{ number_format($product->pivot->unit_price, 2) }}</td>
                                <td>{{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile -->
            <div class="d-md-none mt-3">
                <div class="row">
                    @foreach($delivery->products as $product)
                        <div class="col-12 mb-3">
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <h5 class="card-title mb-1">{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</h5>
                                    <p class="mb-1"><strong>EAN:</strong> {{ $product->ean }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.product.brand') }}:</strong> {{ $product->brand?->name ?? '-' }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.resellers.quantity') }}:</strong> {{ $product->pivot->quantity }}</p>
                                    <p class="mb-1"><strong>{{ __('messages.product.price_btob') }}:</strong> {{ number_format($product->pivot->unit_price, 2) }}</p>
                                    <p class="mb-0"><strong>{{ __('messages.resellers.total_value') ?? 'Total (€)' }}:</strong> {{ number_format($product->pivot->quantity * $product->pivot->unit_price, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @if($resellerType == 'buyer')
        {{-- Onglet Paiements --}}
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
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
                            @foreach($delivery->invoice?->payments ?? [] as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ number_format($payment->amount, 2) }}</td>
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
                        @foreach($delivery->invoice?->payments ?? [] as $payment)
                            <div class="col-12 mb-3">
                                <div class="card shadow-sm">
                                    <div class="card-body p-3">
                                        <p class="mb-1"><strong>Date :</strong> {{ $payment->paid_at->format('d/m/Y H:i') }}</p>
                                        <p class="mb-1"><strong>Montant :</strong> {{ number_format($payment->amount, 2) }}</p>
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
                    Aucun paiement enregistré pour cette livraison.
                </div>
            @endif

            <!-- Bouton modal pour ajouter paiement -->
            <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                Ajouter un paiement
            </button>
        </div>
        @endif
    </div>
</div>

<!-- Modal ajout paiement -->
<!-- Modal ajout paiement -->
 @if($resellerType == 'buyer')
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @if($delivery->invoice)
            <form action="{{ route('reseller-invoices.addPayment', $delivery->invoice->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Ajouter un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    
                    <p><strong>Montant total :</strong> {{ number_format($delivery->invoice?->total_amount ?? 0, 2) }}</p>
                    <p><strong>Déjà payé :</strong> {{ number_format($totalPaid, 2) }}</p>
                    <p><strong>Reste à payer :</strong> {{ number_format($remaining, 2) }}</p>
                    <div class="mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control" max="{{ $remaining }}" required>
                        <div id="amountWarning" class="text-danger mt-1" style="display:none;">
                            ⚠️ Le montant ne peut pas dépasser {{ number_format($remaining, 2) }}.
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
            @else
                <div class="alert alert-warning">
                    ⚠️ Cette livraison n’a pas encore de facture.
                </div>
            @endif
        </div>
    </div>
</div>


@push('scripts')
<script>
document.getElementById('paymentAmount')?.addEventListener('input', function() {
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
@endif
@endsection
