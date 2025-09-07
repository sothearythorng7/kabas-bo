@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.stock_value.title') }}</h1>

    <!-- Recherche -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="{{ __('messages.stock_value.search_placeholder') }}" value="{{ request('search') }}">
            <button class="btn btn-primary">{{ __('messages.btn.search') }}</button>
        </div>
    </form>

    <p><strong>{{ __('messages.stock_value.total_stock') }}</strong> {{ number_format($totalValue, 2) }} €</p>

    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>{{ __('messages.stock_value.ean') }}</th>
                <th>{{ __('messages.stock_value.product') }}</th>
                <th>{{ __('messages.stock_value.remaining_stock') }}</th>
                <th>{{ __('messages.stock_value.estimated_value') }}</th>
                <th>{{ __('messages.stock_value.details') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            @php
                $totalQty = $product->lots->sum('quantity_remaining');
                $totalVal = $product->lots->sum(function($lot) {
                    return $lot->quantity_remaining * $lot->purchase_price;
                });
            @endphp
            <tr>
                <td>{{ $product->ean }}</td>
                <td>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</td>
                <td>{{ $totalQty }}</td>
                <td>{{ number_format($totalVal, 2) }} €</td>
                <td>
                    <button class="btn btn-sm btn-info btn-lots" data-id="{{ $product->id }}">{{ __('messages.stock_value.details') }}</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Pagination -->
    {{ $products->links() }}
</div>

<!-- Modal -->
<div class="modal fade" id="lotsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('messages.stock_value.product_lots') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="lotsContent">
        <!-- Chargé via AJAX -->
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-lots').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.id;
        fetch(`/stock-value/${productId}/lots`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('lotsContent').innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById('lotsModal'));
                modal.show();
            });
    });
});
</script>
@endpush
