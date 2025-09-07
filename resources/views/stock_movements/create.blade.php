@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.stock_movement.create_title') }}</h1>

    <form action="{{ route('stock-movements.store') }}" method="POST" id="stockForm">
        @csrf

        <!-- Sélection des magasins et note -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.stock_movement.source_store') }}</label>
                <select name="from_store_id" class="form-select">
                    <option value="">{{ __('messages.main.select') }}</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('messages.stock_movement.destination_store') }}</label>
                <select name="to_store_id" class="form-select">
                    <option value="">{{ __('messages.main.select') }}</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Note</label>
            <textarea name="note" class="form-control"></textarea>
        </div>

        <hr>
        <h4>{{ __('messages.stock_movement.select_products') }}</h4>
        <input type="text" id="productSearch" class="form-control mb-3" placeholder="{{ __('messages.stock_movement.search_products') }}">

        <!-- Desktop -->
        <div class="d-none d-md-block">
            <table class="table table-sm table-striped" id="productsTable">
                <thead>
                    <tr>
                        <th>{{ __('messages.product.ean') }}</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.stock_movement.source_store') }}</th>
                        <th>{{ __('messages.stock_movement.destination_store') }}</th>
                        <th>{{ __('messages.stock_movement.quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $p)
                    <tr data-product-id="{{ $p->id }}">
                        <td>{{ $p->ean }}</td>
                        <td>{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</td>
                        <td class="source-stock">0</td>
                        <td class="dest-stock">0</td>
                        <td>
                            <input type="number" name="products[{{ $p->id }}]" 
                                class="form-control form-control-sm" min="0" value="0">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile -->
        <div class="d-md-none">
            <div class="row" id="productsTableMobile">
                @foreach($products as $p)
                <div class="col-12 mb-2 product-card" data-product-id="{{ $p->id }}">
                    <div class="card">
                        <div class="card-body p-2">
                            <p class="mb-1"><strong>{{ $p->ean }}</strong></p>
                            <p class="mb-1">{{ $p->name[app()->getLocale()] ?? reset($p->name) }}</p>
                            <p class="mb-1">{{ __('messages.stock_movement.source_store') }}: <span class="source-stock">0</span></p>
                            <p class="mb-1">{{ __('messages.stock_movement.destination_store') }}: <span class="dest-stock">0</span></p>
                            <input type="number" name="products[{{ $p->id }}]" 
                                class="form-control form-control-sm" min="0" value="0">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-success mt-3">
            <i class="bi bi-floppy"></i> {{ __('messages.btn.save') }}
        </button>
    </form>
</div>

<!-- Modal d'erreur source = destination -->
<div class="modal fade" id="storeErrorModal" tabindex="-1" aria-labelledby="storeErrorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="storeErrorModalLabel">Erreur de sélection</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body">
        La source et la destination ne peuvent pas être identiques.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
    input[readonly] {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
</style>
@endpush

@push('scripts')
<script>
const productStocks = @json($products->mapWithKeys(function($p) {
    return [$p->id => $p->realStock];
}));

function updateStocks() {
    const fromStore = document.querySelector('[name="from_store_id"]').value;
    const toStore = document.querySelector('[name="to_store_id"]').value;

    document.querySelectorAll('#productsTable tbody tr, #productsTableMobile .product-card').forEach(row => {
        const productId = row.dataset.productId;
        const sourceStock = productStocks[productId][fromStore] ?? 0;
        const destStock = productStocks[productId][toStore] ?? 0;

        row.querySelector('.source-stock').innerText = sourceStock;
        row.querySelector('.dest-stock').innerText = destStock;

        const qtyInput = row.querySelector('input[type="number"]');
        if (qtyInput) {
            if (sourceStock <= 0) {
                qtyInput.value = 0;
                qtyInput.setAttribute('readonly', 'readonly');
                qtyInput.removeAttribute('max');
            } else {
                qtyInput.removeAttribute('readonly');
                qtyInput.setAttribute('max', sourceStock);
                const val = parseInt(qtyInput.value) || 0;
                if (val > sourceStock) qtyInput.value = sourceStock;
            }
        }
    });
}

// Limiter la saisie
document.querySelectorAll('input[name^="products["]').forEach(input => {
    input.addEventListener('input', function() {
        const max = parseInt(this.getAttribute('max')) || Infinity;
        let value = parseInt(this.value) || 0;
        if (value > max) value = max;
        if (value < 0) value = 0;
        this.value = value;
    });
});

// Initialisation et écouteurs de changement
document.querySelector('[name="from_store_id"]').addEventListener('change', updateStocks);
document.querySelector('[name="to_store_id"]').addEventListener('change', updateStocks);
updateStocks();

// Validation source ≠ destination
function validateStores(selectChanged) {
    const fromStore = document.querySelector('[name="from_store_id"]');
    const toStore = document.querySelector('[name="to_store_id"]');

    if (fromStore.value && fromStore.value === toStore.value) {
        const storeErrorModal = new bootstrap.Modal(document.getElementById('storeErrorModal'));
        storeErrorModal.show();
        if (selectChanged === fromStore) fromStore.value = '';
        else toStore.value = '';
        updateStocks();
    }
}

document.querySelector('[name="from_store_id"]').addEventListener('change', function() {
    validateStores(this);
    updateStocks();
});
document.querySelector('[name="to_store_id"]').addEventListener('change', function() {
    validateStores(this);
    updateStocks();
});

// Gestion du submit : désactiver les inputs non visibles pour éviter les doublons
document.getElementById('stockForm').addEventListener('submit', function() {
    if (window.innerWidth >= 768) {
        document.querySelectorAll('#productsTableMobile .product-card input').forEach(i => i.disabled = true);
        document.querySelectorAll('#productsTable tbody tr input').forEach(i => i.disabled = false);
    } else {
        document.querySelectorAll('#productsTable tbody tr input').forEach(i => i.disabled = true);
        document.querySelectorAll('#productsTableMobile .product-card input').forEach(i => i.disabled = false);
    }
});

// Recherche dynamique
document.getElementById('productSearch').addEventListener('keyup', function() {
    const value = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
    document.querySelectorAll('#productsTableMobile .product-card').forEach(card => {
        card.style.display = card.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>
@endpush
