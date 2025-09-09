@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.title_edit') }} - {{ $product->ean }}<br /><small>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</small></h1>
    <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>

    {{-- Onglets version desktop --}}
    <ul class="nav nav-tabs d-none d-md-flex" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
                <i class="bi bi-list-check"></i> {{ __('messages.product.tab_general') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
                <i class="bi bi-bookmarks"></i> {{ __('messages.product.tab_categories') }}
                <span class="badge bg-{{ ($product->categories->count() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $product->categories->count() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-suppliers" type="button" role="tab">
                 <i class="bi bi-truck"></i> {{ __('messages.product.tab_suppliers') }}
                <span class="badge bg-{{ ($product->suppliers->count() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $product->suppliers->count() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-stores" type="button" role="tab"><i class="bi bi-shop"></i> {{ __('messages.product.tab_stores') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos" type="button" role="tab"><i class="bi bi-images"></i> {{ __('messages.product.tab_photos') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-descriptions" type="button" role="tab"><i class="bi bi-blockquote-right"></i> {{ __('messages.product.tab_descriptions') }}</button></li>
    </ul>

    {{-- Dropdown version mobile --}}
    <div class="d-block d-md-none mb-3">
        <select id="mobile-tabs" class="form-select">
            <option value="#tab-general" selected>{{ __('messages.product.tab_general') }}</option>
            <option value="#tab-categories">{{ __('messages.product.tab_categories') }} ({{ $product->categories->count() ?? 0 }})</option>
            <option value="#tab-suppliers">{{ __('messages.product.tab_suppliers') }} ({{ $product->suppliers->count() ?? 0 }})</option>
            <option value="#tab-stores">{{ __('messages.product.tab_stores') }}</option>
            <option value="#tab-photos">{{ __('messages.product.tab_photos') }}</option>
            <option value="#tab-descriptions">{{ __('messages.product.tab_descriptions') }}</option>
        </select>
    </div>

    <div class="tab-content mt-3">
        {{-- General --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">EAN</label>
                        <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror" value="{{ old('ean', $product->ean) }}" required>
                        @error('ean') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.brand') }}</label>
                        <select name="brand_id" class="form-select">
                            <option value="">--</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id)==$b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.price') }}</label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Name per locale --}}
                @php $locales = config('app.website_locales'); $i=0; @endphp
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $locale)
                        <li class="nav-item">
                            <button class="nav-link @if($i===0) active @endif" data-bs-toggle="tab" data-bs-target="#name-{{ $locale }}" type="button" role="tab">{{ strtoupper($locale) }}</button>
                        </li>
                        @php $i++; @endphp
                    @endforeach
                </ul>
                <div class="tab-content mt-3">
                    @php $i=0; @endphp
                    @foreach($locales as $locale)
                        <div class="tab-pane fade @if($i===0) show active @endif" id="name-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.product.name') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" name="name[{{ $locale }}]" class="form-control"
                                       value="{{ old("name.$locale", $product->name[$locale] ?? '') }}" required>
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('messages.product.color') }}</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color', $product->color) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('messages.product.size') }}</label>
                        <input type="text" name="size" class="form-control" value="{{ old('size', $product->size) }}">
                    </div>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('messages.product.active') }}</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_best_seller" id="is_best_seller" value="1" {{ old('is_best_seller', $product->is_best_seller) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_best_seller">{{ __('messages.product.best_seller') }}</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_resalable" id="is_resalable" value="1"
                        {{ old('is_resalable', $product->is_resalable) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_resalable">{{ __('messages.product.is_resalable') }}</label>
                </div>
                <div class="mt-3">
                    <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
                </div>
            </form>
        </div>

        {{-- Categories --}}
        <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <h5>{{ __('messages.product.categories') }}</h5>
            <ul class="list-group mb-3">
                @forelse($product->categories ?? [] as $category)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $category->fullPathName() }}
                        <form action="{{ route('products.categories.detach', [$product, $category]) }}" method="POST" class="m-0">
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
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_category') }}
            </button>
        </div>

        {{-- Suppliers --}}
        <div class="tab-pane fade" id="tab-suppliers" role="tabpanel">
            <h5>{{ __('messages.product.suppliers') }}</h5>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.supplier.name') }}</th>
                        <th style="width: 150px;">{{ __('messages.supplier.purchase_price') }}</th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>
                                <form action="{{ route('products.suppliers.updatePrice', [$product, $supplier]) }}" method="POST" class="d-flex">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" step="0.01" name="purchase_price" class="form-control form-control-sm"
                                        value="{{ $supplier->pivot->purchase_price }}">
                                    <button class="btn btn-sm btn-success ms-2"><i class="bi bi-check"></i></button>
                                </form>
                            </td>
                            <td>
                                <form action="{{ route('products.suppliers.detach', [$product, $supplier]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">{{ __('messages.product.no_supplier') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_supplier') }}
            </button>
        </div>

        {{-- Stores --}}
        <div class="tab-pane fade" id="tab-stores" role="tabpanel">
            <h5>{{ __('messages.product.stores') }}</h5>
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.store.name') }}</th>
                        <th style="width: 150px;">{{ __('messages.store.stock_quantity') }}</th>
                        <th style="width: 150px;">{{ __('messages.store.stock_alert') }}</th>
                        <th style="width: 120px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stores as $store)
                        @php
                            $realStock = $product->lots
                                ->where('store_id', $store->id)
                                ->sum('quantity_remaining');
                        @endphp
                        <tr>
                            <td>{{ $store->name }}</td>
                            <form action="{{ route('products.stores.updateStock', [$product, $store]) }}" method="POST" class="d-flex">
                                @csrf
                                @method('PUT')
                                <td>
                                    <input type="number" min="0" name="stock_quantity" class="form-control form-control-sm" 
                                           value="{{ $realStock }}">
                                </td>
                                <td>
                                    <input type="number" min="0" name="alert_stock_quantity" class="form-control form-control-sm" 
                                           placeholder="Alert" value="{{ $store->pivot->alert_stock_quantity }}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success ms-2"><i class="bi bi-check"></i></button>
                                </td>
                            </form>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">{{ __('messages.product.no_store') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Photos --}}
        <div class="tab-pane fade" id="tab-photos" role="tabpanel">
            <div class="mb-3">
                <label class="form-label">{{ __('messages.product.upload_photos') }}</label>
                <input type="file" name="photos[]" class="form-control" multiple>
            </div>
            @if($product->images->count())
                <div class="mb-2">{{ __('messages.product.existing_photos') }}:</div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($product->images as $img)
                        <label class="border rounded p-2 d-inline-flex align-items-center gap-2">
                            <input type="radio" name="primary_image_id" value="{{ $img->id }}" @checked($img->is_primary)>
                            <img src="{{ asset('storage/'.$img->path) }}" alt="" style="height:70px;">
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Descriptions --}}
        <div class="tab-pane fade" id="tab-descriptions" role="tabpanel">
            <form action="{{ route('products.descriptions.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                @php $i=0; @endphp
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $locale)
                        <li class="nav-item">
                            <button class="nav-link @if($i===0) active @endif" data-bs-toggle="tab" data-bs-target="#desc-{{ $locale }}" type="button" role="tab">
                                {{ strtoupper($locale) }}
                            </button>
                        </li>
                        @php $i++; @endphp
                    @endforeach
                </ul>
                <div class="tab-content mt-3">
                    @php $i=0; @endphp
                    @foreach($locales as $locale)
                        <div class="tab-pane fade @if($i===0) show active @endif" id="desc-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.product.description') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="description[{{ $locale }}]" class="form-control summernote" rows="5">{{ old("description.$locale", $product->description[$locale] ?? '') }}</textarea>
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>
                <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
            </form>
        </div>
    </div>
</div>
{{-- Modal Ajouter une Catégorie --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('products.categories.attach', $product) }}" method="POST">
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
          <button type="submit" class="btn btn-success">{{ __('messages.btn.add') }}</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Modal Ajouter un Supplier --}}
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('products.suppliers.attach', $product) }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">{{ __('messages.product.add_supplier') }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">{{ __('messages.supplier.name') }}</label>
            <select name="supplier_id" class="form-select" required>
              <option value="">--</option>
              @foreach($allSuppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ __('messages.supplier.purchase_price') }}</label>
            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">{{ __('messages.btn.add') }}</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>


{{-- Script pour dropdown mobile --}}
<script>
    document.getElementById('mobile-tabs').addEventListener('change', function() {
        let target = this.value;
        let tab = document.querySelector(`[data-bs-target="${target}"]`);
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    });
</script>
@endsection
