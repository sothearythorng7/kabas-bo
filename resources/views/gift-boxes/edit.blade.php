@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.gift_boxes.edit') }}<br /><small>{{ $giftBox->name['fr'] ?? $giftBox->name['en'] ?? 'N/A' }}</small></h1>

    <a href="{{ route('gift-boxes.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.gift_boxes.back_to_list') }}
    </a>

    {{-- Onglets --}}
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                <i class="bi bi-list-check"></i> {{ __('messages.gift_boxes.general') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
                <i class="bi bi-bookmarks"></i> {{ __('messages.product.categories') }}
                <span class="badge bg-{{ ($giftBox->categories->count() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $giftBox->categories->count() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-products" type="button" role="tab">
                <i class="bi bi-box-seam"></i> {{ __('messages.gift_boxes.products_in_box') }}
                <span class="badge bg-{{ ($giftBox->products->count() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $giftBox->products->count() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos" type="button" role="tab">
                <i class="bi bi-images"></i> {{ __('messages.gift_boxes.photos') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-descriptions" type="button" role="tab">
                <i class="bi bi-blockquote-right"></i> {{ __('messages.gift_boxes.descriptions') }}
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3">
        {{-- Onglet Général --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('gift-boxes.update', $giftBox) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.form.ean_optional') }}</label>
                        <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror"
                               value="{{ old('ean', $giftBox->ean) }}">
                        @error('ean') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.form.brand') }}</label>
                        <select name="brand_id" class="form-select">
                            <option value="">--</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $giftBox->brand_id) == $brand->id)>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.gift_boxes.public_price') }} *</label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror"
                               value="{{ old('price', $giftBox->price) }}" required>
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.gift_boxes.b2b_price') }}</label>
                        <input type="number" step="0.01" name="price_btob" class="form-control @error('price_btob') is-invalid @enderror"
                               value="{{ old('price_btob', $giftBox->price_btob) }}">
                        @error('price_btob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Noms par langue --}}
                @php $locales = config('app.website_locales', ['fr', 'en']); @endphp
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $i => $locale)
                        <li class="nav-item">
                            <button class="nav-link {{ $i == 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#name-{{ $locale }}" type="button" role="tab">
                                {{ strtoupper($locale) }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content border border-top-0 p-3 mb-3">
                    @foreach($locales as $i => $locale)
                        <div class="tab-pane fade {{ $i == 0 ? 'show active' : '' }}" id="name-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.form.name') }} ({{ strtoupper($locale) }}) *</label>
                                <input type="text" name="name[{{ $locale }}]" class="form-control @error("name.{$locale}") is-invalid @enderror"
                                       value="{{ old("name.{$locale}", $giftBox->name[$locale] ?? '') }}" required>
                                @error("name.{$locale}") <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                   @checked(old('is_active', $giftBox->is_active))>
                            <label class="form-check-label" for="is_active">{{ __('messages.form.active') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_best_seller" id="is_best_seller" value="1"
                                   @checked(old('is_best_seller', $giftBox->is_best_seller))>
                            <label class="form-check-label" for="is_best_seller">{{ __('messages.gift_boxes.best_seller') }}</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ __('messages.Enregistrer') }}
                </button>
            </form>
        </div>

        {{-- Onglet Catégories --}}
        <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <h5>{{ __('messages.product.categories') }}</h5>
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_category') }}
            </button>
            <ul class="list-group mb-3">
                @forelse($giftBox->categories ?? [] as $category)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $category->fullPathName() }}
                        <form action="{{ route('gift-boxes.categories.detach', [$giftBox, $category]) }}" method="POST" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('messages.product.no_category') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Onglet Produits --}}
        <div class="tab-pane fade" id="tab-products" role="tabpanel">
            <h5>{{ __('messages.gift_boxes.products_in_box') }}</h5>
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.gift_boxes.add_product') }}
            </button>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.resellers.product') }}</th>
                        <th style="width: 150px;">{{ __('messages.form.quantity') }}</th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($giftBox->products as $product)
                        <tr>
                            <td>{{ $product->ean }} - {{ $product->name['fr'] ?? $product->name['en'] ?? 'N/A' }}</td>
                            <td>
                                <form action="{{ route('gift-boxes.products.updateQuantity', [$giftBox, $product]) }}" method="POST" class="d-flex">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" step="1" min="1" name="quantity" class="form-control form-control-sm"
                                        value="{{ $product->pivot->quantity }}">
                                    <button class="btn btn-sm btn-success ms-2"><i class="bi bi-check"></i></button>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route('gift-boxes.products.detach', [$giftBox, $product]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">{{ __('messages.gift_boxes.no_products') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Onglet Photos --}}
        <div class="tab-pane fade" id="tab-photos" role="tabpanel">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.gift_boxes.upload_photos') }}</label>
                <input type="file" id="photo-upload" class="form-control" accept="image/*">
            </div>

            <div class="row" id="photos-grid">
                @foreach($giftBox->images as $image)
                    <div class="col-md-3 mb-3" data-image-id="{{ $image->id }}">
                        <div class="card">
                            <img src="{{ asset('storage/' . $image->path) }}" class="card-img-top" alt="Image">
                            <div class="card-body">
                                @if($image->is_primary)
                                    <span class="badge bg-success mb-2">{{ __('messages.gift_boxes.primary_image') }}</span>
                                @else
                                    <button type="button" class="btn btn-sm btn-primary set-primary" data-image-id="{{ $image->id }}">
                                        {{ __('messages.gift_boxes.set_as_primary') }}
                                    </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-danger delete-image" data-image-id="{{ $image->id }}">
                                    <i class="bi bi-trash"></i> {{ __('messages.Supprimer') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Onglet Descriptions --}}
        <div class="tab-pane fade" id="tab-descriptions" role="tabpanel">
            <form action="{{ route('gift-boxes.update', $giftBox) }}" method="POST">
                @csrf
                @method('PUT')

                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $i => $locale)
                        <li class="nav-item">
                            <button class="nav-link {{ $i == 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                    data-bs-target="#desc-{{ $locale }}" type="button" role="tab">
                                {{ strtoupper($locale) }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content border border-top-0 p-3 mb-3">
                    @foreach($locales as $i => $locale)
                        <div class="tab-pane fade {{ $i == 0 ? 'show active' : '' }}" id="desc-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.common.description') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="description[{{ $locale }}]" class="form-control" rows="6">{{ old("description.{$locale}", $giftBox->description[$locale] ?? '') }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ __('messages.Enregistrer') }}
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ajouter une Catégorie --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('gift-boxes.categories.attach', $giftBox) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">{{ __('messages.product.add_category') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">{{ __('messages.product.category') }}</label>
            <select name="category_id" class="form-select" required>
              <option value="">--</option>
              @foreach(app(\App\Http\Controllers\ProductController::class)->buildCategoryPathOptions() as $id => $path)
                <option value="{{ $id }}">{{ $path }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
          <button type="submit" class="btn btn-success">{{ __('messages.btn.add') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Ajouter un Produit --}}
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('gift-boxes.products.attach', $giftBox) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">{{ __('messages.gift_boxes.add_product') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">{{ __('messages.gift_boxes.search_product') }}</label>
            <input type="text" class="form-control" id="product_search" placeholder="{{ __('messages.product.search_product') }}">
            <input type="hidden" name="product_id" id="product_id" required>
            <div id="product_results" class="list-group position-absolute zindex-1" style="max-height:200px; overflow-y:auto;"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ __('messages.form.quantity') }}</label>
            <input type="number" class="form-control" name="quantity" id="product_quantity" min="1" value="1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
          <button type="submit" class="btn btn-success">{{ __('messages.btn.add') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Recherche produit via Ajax (Modal Ajout)
let searchInput = document.getElementById('product_search');
let resultsDiv = document.getElementById('product_results');
let hiddenInput = document.getElementById('product_id');
let quantityInput = document.getElementById('product_quantity');

if (searchInput) {
    let timeout = null;
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        let q = this.value;
        if(q.length < 2) { resultsDiv.innerHTML=''; return; }
        timeout = setTimeout(() => {
            fetch('/products/search?q='+encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                data.forEach(p => {
                    let a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action';
                    a.textContent = p.ean+' - '+(p.name.fr||Object.values(p.name)[0]);
                    a.dataset.id = p.id;
                    a.addEventListener('click', function(e){
                        e.preventDefault();
                        hiddenInput.value = this.dataset.id;
                        searchInput.value = this.textContent;
                        resultsDiv.innerHTML = '';
                    });
                    resultsDiv.appendChild(a);
                });
            });
        }, 300);
    });
}

// Gestion des photos
document.getElementById('photo-upload')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', '{{ csrf_token() }}');

    fetch('{{ route("gift-boxes.images.upload", $giftBox) }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});

// Supprimer une image
document.querySelectorAll('.delete-image').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('{{ __('messages.gift_boxes.confirm_delete_image') }}')) return;

        const imageId = this.dataset.imageId;
        fetch(`{{ route("gift-boxes.images.delete", ["giftBox" => $giftBox, "image" => "__ID__"]) }}`.replace('__ID__', imageId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});

// Définir image principale
document.querySelectorAll('.set-primary').forEach(btn => {
    btn.addEventListener('click', function() {
        const imageId = this.dataset.imageId;
        fetch(`{{ route("gift-boxes.images.setPrimary", ["giftBox" => $giftBox, "image" => "__ID__"]) }}`.replace('__ID__', imageId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    });
});
</script>

@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">{{ __('messages.flash.success') }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif
@endsection
