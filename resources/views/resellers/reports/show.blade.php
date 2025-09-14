@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>Sales Report #{{ $report->id }} for {{ $reseller->name }}</h1>
    <p><strong>Created at:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</p>

    <div class="mb-3">
        <a href="{{ route('resellers.show', $reseller) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Reseller
        </a>
    </div>

    {{-- Paiements --}}
    @if($report->invoice)
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Montant total à payer</h6>
                    <p class="card-text">${{ number_format($report->invoice->total_amount, 2) }}</p>
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

    {{-- Liste des produits vendus --}}
    <div class="table-responsive mb-3">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>Product Name</th>
                    <th>Unit Price</th>
                    <th>Quantity Sold</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report->items as $item)
                    <tr>
                        <td>{{ $item->product->ean ?? '-' }}</td>
                        <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                        <td>{{ number_format($item->unit_price, 2, ',', ' ') }}</td>
                        <td>{{ $item->quantity_sold }}</td>
                        <td>{{ number_format($item->quantity_sold * $item->unit_price, 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total Report Value:</th>
                    <th>
                        {{ number_format($report->items->sum(fn($i) => $i->quantity_sold * $i->unit_price), 2, ',', ' ') }} €
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Liste des paiements --}}
    @if($report->invoice && $paymentsCount > 0)
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
                    @foreach($report->invoice->payments as $payment)
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
                @foreach($report->invoice->payments as $payment)
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
    @elseif($report->invoice)
        <div class="alert alert-info">
            Aucun paiement enregistré pour ce report.
        </div>
    @endif

    {{-- Bouton modal pour ajouter paiement --}}
    @if($report->invoice)
        <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
            Ajouter un paiement
        </button>
    @endif
</div>
@php
    $resellerParam = ($reseller->is_shop ?? false) ? 'shop-'.$reseller->id : $reseller->id;
@endphp
{{-- Modal ajout paiement --}}
@if($report->invoice)
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('resellers.report.addPayment', ['reseller' => $resellerParam ?? $reseller->id, 'report' => $report]) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Ajouter un paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Montant total :</strong> {{ number_format($report->invoice->total_amount, 2) }}</p>
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
