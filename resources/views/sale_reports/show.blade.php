@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('Sale Report') }} - {{ $saleReport->store->name }}</h1>
    <p>{{ __('Period') }}: {{ $saleReport->period_start->format('d/m/Y') }} - {{ $saleReport->period_end->format('d/m/Y') }}</p>
    @if($saleReport->report_file_path)
    <div class="mt-3">
        <h5>Rapport PDF :</h5>
        <a href="{{ Storage::url($saleReport->report_file_path) }}" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-pdf"></i> Télécharger le rapport
        </a>
    </div>

    @if($saleReport->sent_at)
        <div class="alert alert-info mt-2">
            Envoyé le {{ $saleReport->sent_at->format('d/m/Y H:i') }} à {{ $saleReport->sent_to }}
        </div>
    @endif
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Quantity Sold') }}</th>
                <th>{{ __('Unit Price') }}</th>
                <th>{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($saleReport->items as $item)
                <tr>
                    <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                    <td>{{ $item->quantity_sold }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
