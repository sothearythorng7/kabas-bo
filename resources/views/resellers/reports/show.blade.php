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

    {{-- Liste des produits vendus --}}
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>EAN</th>
                    <th>Product Name</th>
                    <th>Unit Price (€)</th>
                    <th>Quantity Sold</th>
                    <th>Total (€)</th>
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
</div>
@endsection
