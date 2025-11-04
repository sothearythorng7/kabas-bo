@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.title_edit') }} - {{ $product->ean }}<br /><small>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</small></h1>
    <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>
    <a href="{{ $product->publicUrl() }}" target="_blank" rel="noopener"
    class="btn btn-primary mb-3 me-2">
        <i class="bi bi-box-arrow-up-right"></i> {{ __('messages.product.view_public') ?? 'Voir sur le site' }}
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
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-variations" type="button" role="tab">
                <i class="bi bi-bezier2"></i> {{ __('messages.product.tab_variations') }}
                <span class="badge bg-{{ ($product->variations->count() ?? 0) > 0 ? 'success' : 'danger' }}">
                    {{ $product->variations->count() ?? 0 }}
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
            <option value="#tab-variations">{{ __('messages.product.tab_variations') }} ({{ $product->variations->count() ?? 0 }})</option>

        </select>
    </div>

    <div class="tab-content mt-3">
        {{-- General --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@t("ean")</label>
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
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.price_btob') }}</label>
                        <input type="number" step="0.01" name="price_btob" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price_btob) }}">
                        @error('price_btob') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_category') }}
            </button>
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
        </div>

        {{-- Suppliers --}}
        <div class="tab-pane fade" id="tab-suppliers" role="tabpanel">
            <h5>{{ __('messages.product.suppliers') }}</h5>
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.product.add_supplier') }}
            </button>
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
                            // Nouveau calcul basé sur les batches
                            $realStock = $product->stockBatches()
                                ->where('store_id', $store->id)
                                ->sum('quantity');
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
                        <tr>
                            <td colspan="4" class="text-muted">{{ __('messages.product.no_store') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Photos --}}
        <div class="tab-pane fade" id="tab-photos" role="tabpanel">
            <form action="{{ route('products.photos.upload', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.product.upload_photos') }}</label>
                    <input type="file" name="photos[]" class="form-control" multiple>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">{{ __('messages.btn.save') }}</button>
                </div>
            </form>

            {{-- Liste des images avec possibilité de supprimer --}}
            @if($product->images->count())
                <div class="d-flex flex-wrap gap-3 mt-3">
                    @foreach($product->images as $img)
                    <div class="image-item">
                        <img src="{{ asset('storage/' . $img->path) }}" alt="" width="100">
                        
                        <form action="{{ route('products.photos.setPrimary', [$product, $img]) }}" method="POST">
                            @csrf
                            <input type="radio" name="primary_photo" onchange="this.form.submit()" 
                                {{ $img->is_primary ? 'checked' : '' }}>
                        </form>

                        <form action="{{ route('products.photos.delete', [$product, $img]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Delete</button>
                        </form>
                    </div>
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


        <div class="tab-pane fade" id="tab-variations" role="tabpanel">
            <h5>{{ __('messages.product.variations') }}</h5>
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.btn.add_variation') }}
            </button>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.variation.type') }}</th>
                        <th>{{ __('messages.variation.value') }}</th>
                        <th>{{ __('messages.variation.linked_product') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($product->variations as $var)
                    <tr>
                        <td>{{ $var->type->name }}</td>
                        <td>{{ $var->value->value }}</td>
                        <td>
                            <a href="{{route('products.edit', $var->linkedProduct)}}" target="_blank">
                                {{ $var->linkedProduct->ean }} - {{ $var->linkedProduct->name['fr'] ?? reset($var->linkedProduct->name) }}
                            </a>
                        </td>
                        <td>
                            @php
                                $linkedProductName = $var->linkedProduct->name['fr'] ?? reset($var->linkedProduct->name);
                                $linkedProductDisplay = $var->linkedProduct->ean . ' - ' . $linkedProductName;
                            @endphp
                            <button type="button" class="btn btn-sm btn-primary me-2"
                                onclick='openEditVariationModal({{ $var->id }}, {{ $var->variation_type_id }}, {{ $var->variation_value_id }}, {{ $var->linked_product_id }}, {{ json_encode($linkedProductDisplay) }})'>
                                <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
                            </button>
                            <form method="POST" action="{{ route('products.variations.destroy', [$product, $var->id]) }}" onsubmit="return confirm('Confirm?')" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">{{ __('messages.btn.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="modal fade" id="addVariationModal" tabindex="-1" aria-labelledby="addVariationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="addVariationForm" method="POST" action="{{ route('products.variations.store', $product) }}">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title" id="addVariationModalLabel">{{ __('messages.btn.add_variation') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.type') }}</label>
                                        <select class="form-select" name="variation_type_id" id="variation_type">
                                            <option value="">--</option>
                                            @foreach($types as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.value') }}</label>
                                        <select class="form-select" name="variation_value_id" id="variation_value">
                                            <option value="">--</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.linked_product') }}</label>
                                        <input type="text" class="form-control" id="linked_product_search" placeholder="EAN / name">
                                        <input type="hidden" name="linked_product_id" id="linked_product_id">
                                        <div id="linked_product_results" class="list-group position-absolute zindex-1" style="max-height:200px; overflow-y:auto;"></div>
                                    </div>
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

            <div class="modal fade" id="editVariationModal" tabindex="-1" aria-labelledby="editVariationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="editVariationForm" method="POST" onsubmit="return validateEditForm()">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title" id="editVariationModalLabel">{{ __('messages.btn.edit_variation') ?? 'Modifier la variation' }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.type') }}</label>
                                        <select class="form-select" name="variation_type_id" id="edit_variation_type" required>
                                            <option value="">--</option>
                                            @foreach($types as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.value') }}</label>
                                        <select class="form-select" name="variation_value_id" id="edit_variation_value" required>
                                            <option value="">--</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('messages.variation.linked_product') }}</label>
                                        <input type="text" class="form-control" id="edit_linked_product_search" placeholder="EAN / name" required>
                                        <input type="hidden" name="linked_product_id" id="edit_linked_product_id" required>
                                        <div id="edit_linked_product_results" class="list-group position-absolute zindex-1" style="max-height:200px; overflow-y:auto;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('messages.btn.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <script>
            // Ajax pour récupérer les valeurs selon le type (Modal Ajout)
            document.getElementById('variation_type').addEventListener('change', function() {
                let typeId = this.value;
                let valueSelect = document.getElementById('variation_value');
                valueSelect.innerHTML = '<option>Loading...</option>';
                fetch('/variation-types/'+typeId+'/values')
                    .then(res => res.json())
                    .then(data => {
                        valueSelect.innerHTML = '<option value="">--</option>';
                        data.forEach(v => {
                            console.log(v);
                            let opt = document.createElement('option');
                            opt.value = v.id;
                            opt.text = v.value;
                            valueSelect.appendChild(opt);
                        });
                    });
            });

            // Ajax pour récupérer les valeurs selon le type (Modal Édition)
            document.getElementById('edit_variation_type').addEventListener('change', function() {
                let typeId = this.value;
                let valueSelect = document.getElementById('edit_variation_value');
                valueSelect.innerHTML = '<option>Loading...</option>';
                fetch('/variation-types/'+typeId+'/values')
                    .then(res => res.json())
                    .then(data => {
                        valueSelect.innerHTML = '<option value="">--</option>';
                        data.forEach(v => {
                            let opt = document.createElement('option');
                            opt.value = v.id;
                            opt.text = v.value;
                            valueSelect.appendChild(opt);
                        });
                    });
            });

            // Recherche produit via Ajax (Modal Ajout)
            let searchInput = document.getElementById('linked_product_search');
            let resultsDiv = document.getElementById('linked_product_results');
            let hiddenInput = document.getElementById('linked_product_id');

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

            // Recherche produit via Ajax (Modal Édition)
            let editSearchInput = document.getElementById('edit_linked_product_search');
            let editResultsDiv = document.getElementById('edit_linked_product_results');
            let editHiddenInput = document.getElementById('edit_linked_product_id');

            let editTimeout = null;
            editSearchInput.addEventListener('input', function() {
                clearTimeout(editTimeout);
                let q = this.value;
                if(q.length < 2) { editResultsDiv.innerHTML=''; return; }
                editTimeout = setTimeout(() => {
                    fetch('/products/search?q='+encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        editResultsDiv.innerHTML = '';
                        data.forEach(p => {
                            let a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action';
                            a.textContent = p.ean+' - '+(p.name.fr||Object.values(p.name)[0]);
                            a.dataset.id = p.id;
                            a.addEventListener('click', function(e){
                                e.preventDefault();
                                editHiddenInput.value = this.dataset.id;
                                editSearchInput.value = this.textContent;
                                editResultsDiv.innerHTML = '';
                            });
                            editResultsDiv.appendChild(a);
                        });
                    });
                }, 300);
            });

            // Fonction pour ouvrir le modal d'édition avec les données pré-remplies
            function openEditVariationModal(variationId, typeId, valueId, linkedProductId, linkedProductText) {
                // Définir l'URL du formulaire
                const form = document.getElementById('editVariationForm');
                form.action = '{{ route("products.variations.update", [$product, ":id"]) }}'.replace(':id', variationId);

                // Pré-remplir le type
                document.getElementById('edit_variation_type').value = typeId;

                // Charger les valeurs du type et pré-sélectionner
                fetch('/variation-types/' + typeId + '/values')
                    .then(res => res.json())
                    .then(data => {
                        let valueSelect = document.getElementById('edit_variation_value');
                        valueSelect.innerHTML = '<option value="">--</option>';
                        data.forEach(v => {
                            let opt = document.createElement('option');
                            opt.value = v.id;
                            opt.text = v.value;
                            if (v.id == valueId) {
                                opt.selected = true;
                            }
                            valueSelect.appendChild(opt);
                        });
                    });

                // Pré-remplir le produit lié
                document.getElementById('edit_linked_product_id').value = linkedProductId;
                document.getElementById('edit_linked_product_search').value = linkedProductText;

                // Ouvrir le modal
                const modal = new bootstrap.Modal(document.getElementById('editVariationModal'));
                modal.show();
            }

            // Fonction de validation avant soumission
            function validateEditForm() {
                const linkedProductId = document.getElementById('edit_linked_product_id').value;
                const typeId = document.getElementById('edit_variation_type').value;
                const valueId = document.getElementById('edit_variation_value').value;

                if (!linkedProductId) {
                    alert('Veuillez sélectionner un produit lié');
                    return false;
                }
                if (!typeId) {
                    alert('Veuillez sélectionner un type de variation');
                    return false;
                }
                if (!valueId) {
                    alert('Veuillez sélectionner une valeur de variation');
                    return false;
                }

                return true;
            }
        </script>
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

    document.addEventListener('DOMContentLoaded', function() {
        // Activer l'onglet correspondant à l'ancre dans l'URL
        const hash = window.location.hash;
        if (hash) {
            const tabBtn = document.querySelector(`.nav-tabs button[data-bs-target="${hash}"]`);
            if (tabBtn) {
                new bootstrap.Tab(tabBtn).show();
            }

            // Pour le dropdown mobile
            const mobileSelect = document.getElementById('mobile-tabs');
            if (mobileSelect) {
                mobileSelect.value = hash;
            }
        }

        // Mettre à jour le dropdown mobile quand l'utilisateur change l'onglet desktop
        document.querySelectorAll('.nav-tabs button[data-bs-toggle="tab"]').forEach(btn => {
            btn.addEventListener('shown.bs.tab', function (e) {
                const target = e.target.getAttribute('data-bs-target');
                const mobileSelect = document.getElementById('mobile-tabs');
                if (mobileSelect) {
                    mobileSelect.value = target;
                }
            });
        });

        // Soumission du radio pour la photo principale
        document.querySelectorAll('input[name="primary_photo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
@endsection
