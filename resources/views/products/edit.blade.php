@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.title_edit') }} - {{ $product->ean }}<br /><small>{{ $product->name[app()->getLocale()] ?? reset($product->name) }}</small></h1>

    {{-- Alertes produit --}}
    @if(!empty($productAlerts))
    <div class="alert alert-warning mb-3">
        <h6 class="alert-heading mb-2"><i class="bi bi-exclamation-triangle"></i> {{ __('messages.product.alerts') }}</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach($productAlerts as $alert)
                <span class="badge bg-{{ $alert['color'] }}">
                    <i class="bi {{ $alert['icon'] }}"></i> {{ $alert['message'] }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>
    <a href="{{ $product->publicUrl() }}" target="_blank" rel="noopener"
    class="btn btn-primary mb-3 me-2">
        <i class="bi bi-box-arrow-up-right"></i> {{ __('messages.product.view_public') ?? 'Voir sur le site' }}
    </a>
    <form action="{{ route('products.duplicate', $product) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-warning mb-3" onclick="return confirm('{{ __('messages.product.confirm_duplicate') }}')">
            <i class="bi bi-copy"></i> {{ __('messages.product.duplicate') }}
        </button>
    </form>

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
                @php $varCount = $product->variation_group_id ? $product->variationGroup->products()->count() : 0; @endphp
                <span class="badge bg-{{ $varCount > 0 ? 'success' : 'danger' }}">
                    {{ $varCount }}
                </span>
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-stores" type="button" role="tab"><i class="bi bi-shop"></i> {{ __('messages.product.tab_stores') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos" type="button" role="tab"><i class="bi bi-images"></i> {{ __('messages.product.tab_photos') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-descriptions" type="button" role="tab"><i class="bi bi-blockquote-right"></i> {{ __('messages.product.tab_descriptions') }}</button></li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-barcodes" type="button" role="tab">
                <i class="bi bi-upc-scan"></i> {{ __('messages.product.tab_barcodes') ?? 'Codes-barres' }}
                <span class="badge bg-{{ ($product->barcodes->count() ?? 0) > 0 ? 'success' : 'secondary' }}">
                    {{ $product->barcodes->count() ?? 0 }}
                </span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-seo" type="button" role="tab">
                <i class="bi bi-search"></i> {{ __('messages.product.tab_seo') }}
            </button>
        </li>
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
            <option value="#tab-variations">{{ __('messages.product.tab_variations') }} ({{ $varCount }})</option>
            <option value="#tab-barcodes">{{ __('messages.product.tab_barcodes') ?? 'Codes-barres' }} ({{ $product->barcodes->count() ?? 0 }})</option>
            <option value="#tab-seo">{{ __('messages.product.tab_seo') }}</option>
        </select>
    </div>

    <div class="tab-content mt-3">
        {{-- General --}}
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.ean') }}</label>
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
                        <input type="number" step="0.00001" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price) }}">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.price_btob') }}</label>
                        <input type="number" step="0.00001" name="price_btob" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $product->price_btob) }}">
                        @error('price_btob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.shipping_weight') }}</label>
                        <div class="input-group">
                            <input type="number" step="1" min="0" name="shipping_weight"
                                   class="form-control" value="{{ old('shipping_weight', $product->shipping_weight) }}">
                            <span class="input-group-text">g</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.gender') }}</label>
                        <select name="gender" class="form-select">
                            <option value="">{{ __('messages.product.gender_none') }}</option>
                            @foreach(['male', 'female', 'unisex'] as $g)
                                <option value="{{ $g }}" @selected(old('gender', $product->gender) === $g)>{{ __('messages.product.gender_' . $g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('messages.product.age_group') }}</label>
                        <select name="age_group" class="form-select">
                            <option value="">{{ __('messages.product.age_group_none') }}</option>
                            @foreach(['adult', 'kids', 'toddler', 'infant', 'newborn'] as $a)
                                <option value="{{ $a }}" @selected(old('age_group', $product->age_group) === $a)>{{ __('messages.product.age_group_' . $a) }}</option>
                            @endforeach
                        </select>
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
                                <label class="form-label">{{ __('messages.product.name') }} ({{ strtoupper($locale) }})
                                    @if($locale === 'en')
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="text" name="name[{{ $locale }}]" class="form-control"
                                       value="{{ old("name.$locale", $product->name[$locale] ?? '') }}" {{ $locale === 'en' ? 'required' : '' }}>
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">{{ __('messages.product.active_website') }}</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active_pos" id="is_active_pos" value="1" {{ old('is_active_pos', $product->is_active_pos) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active_pos">{{ __('messages.product.active_pos') }}</label>
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
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="allow_overselling" id="allow_overselling" value="1"
                        {{ old('allow_overselling', $product->allow_overselling) ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_overselling">{{ __('messages.product.allow_overselling') }}</label>
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
                                    <input type="number" step="0.00001" name="purchase_price" class="form-control form-control-sm"
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
            <h5><i class="bi bi-shop"></i> {{ __('messages.product.stores') }}</h5>
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
                    @forelse($stores as $store)
                        @php
                            // Calcul du stock réel basé sur les batches
                            $realStock = $product->stockBatches()
                                ->where('store_id', $store->id)
                                ->sum('quantity');
                            // Récupérer l'alerte stock depuis le pivot si disponible
                            $alertStock = $storePivot[$store->id]['alert_stock_quantity'] ?? null;
                        @endphp
                        <tr class="{{ $store->type === 'warehouse' ? 'table-info' : '' }}">
                            <td>
                                {{ $store->name }}
                                @if($store->type === 'warehouse')
                                    <span class="badge bg-info ms-1">Warehouse</span>
                                @endif
                            </td>
                            <form action="{{ route('products.stores.updateStock', [$product, $store]) }}" method="POST" class="d-flex">
                                @csrf
                                @method('PUT')
                                <td>
                                    <input type="number" min="0" name="stock_quantity" class="form-control form-control-sm"
                                        value="{{ $realStock }}">
                                </td>
                                <td>
                                    <input type="number" min="0" name="alert_stock_quantity" class="form-control form-control-sm"
                                        placeholder="{{ __('messages.product.stock_alert') }}" value="{{ $alertStock }}">
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

            {{-- Resellers Stock --}}
            @php
                $resellersWithStock = \App\Models\StockBatch::where('product_id', $product->id)
                    ->whereNotNull('reseller_id')
                    ->where('quantity', '>', 0)
                    ->with('reseller')
                    ->get()
                    ->groupBy('reseller_id')
                    ->map(function($batches) {
                        return [
                            'reseller' => $batches->first()->reseller,
                            'quantity' => $batches->sum('quantity'),
                        ];
                    })
                    ->sortByDesc('quantity')
                    ->values();
            @endphp

            @if($resellersWithStock->count() > 0)
            <hr class="my-4">
            <h5><i class="bi bi-people"></i> {{ __('messages.product.resellers_stock') }}</h5>
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('messages.product.reseller') }}</th>
                        <th style="width: 150px;" class="text-end">{{ __('messages.store.stock_quantity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resellersWithStock as $item)
                        <tr>
                            <td>
                                <a href="{{ route('resellers.show', $item['reseller']->id) }}">
                                    {{ $item['reseller']->name }}
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-secondary fs-6">{{ $item['quantity'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th>{{ __('messages.product.total_resellers_stock') }}</th>
                        <th class="text-end">
                            <span class="badge bg-primary fs-6">{{ $resellersWithStock->sum('quantity') }}</span>
                        </th>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>

        {{-- Photos --}}
        <div class="tab-pane fade" id="tab-photos" role="tabpanel">
            <form action="{{ route('products.photos.upload', $product) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.product.upload_images') }}</label>
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
                            <button type="submit">{{ __('messages.btn.delete') }}</button>
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

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Group info --}}
            @if($product->variation_group_id)
                <div class="alert alert-info d-flex align-items-center justify-content-between mb-3">
                    <span><i class="bi bi-collection"></i> <strong>{{ __('messages.variation.group') ?? 'Groupe' }}:</strong> {{ $product->variationGroup->name ?? '—' }}</span>
                </div>
            @endif

            {{-- Current product attributes --}}
            @if($product->variation_group_id)
                <div class="card mb-3">
                    <div class="card-header"><strong>{{ __('messages.variation.this_product_attributes') ?? "Attributs de ce produit" }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('products.variations.updateSelf', $product) }}">
                            @csrf @method('PUT')
                            <div id="selfAttributeRows">
                                @forelse($product->variationAttributes as $i => $attr)
                                    <div class="row g-2 mb-2 attr-row">
                                        <div class="col-md-4">
                                            <select class="form-select attr-type-select" name="attributes[{{ $i }}][variation_type_id]" required>
                                                <option value="">--</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}" {{ $attr->variation_type_id == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-select attr-value-select" name="attributes[{{ $i }}][variation_value_id]" required data-selected="{{ $attr->variation_value_id }}">
                                                <option value="{{ $attr->variation_value_id }}">{{ $attr->value->value }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-attr-row"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="row g-2 mb-2 attr-row">
                                        <div class="col-md-4">
                                            <select class="form-select attr-type-select" name="attributes[0][variation_type_id]" required>
                                                <option value="">--</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <select class="form-select attr-value-select" name="attributes[0][variation_value_id]" required>
                                                <option value="">--</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-attr-row"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="addSelfAttrRow"><i class="bi bi-plus"></i> {{ __('messages.variation.add_attribute') ?? 'Ajouter attribut' }}</button>
                            <br>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check"></i> {{ __('messages.btn.save') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Group members table --}}
            @if($product->variation_group_id && isset($groupProducts) && $groupProducts->count() > 1)
                <h6 class="mt-4">{{ __('messages.variation.group_members') ?? 'Produits du groupe' }}</h6>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>EAN</th>
                            <th>{{ __('messages.product.name') ?? 'Nom' }}</th>
                            <th>{{ __('messages.variation.attributes') ?? 'Attributs' }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupProducts as $gp)
                            <tr class="{{ $gp->id === $product->id ? 'table-active' : '' }}">
                                <td><code>{{ $gp->ean }}</code></td>
                                <td>
                                    @if($gp->id === $product->id)
                                        <strong>{{ $gp->name['fr'] ?? reset($gp->name) }}</strong> <span class="badge bg-success">{{ __('messages.variation.current') ?? 'actuel' }}</span>
                                    @else
                                        <a href="{{ route('products.edit', $gp) }}#tab-variations" target="_blank">{{ $gp->name['fr'] ?? reset($gp->name) }}</a>
                                    @endif
                                </td>
                                <td>
                                    @foreach($gp->variationAttributes as $attr)
                                        <span class="badge bg-secondary">{{ $attr->type->name }}: {{ $attr->value->value }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @if($gp->id !== $product->id)
                                        <form method="POST" action="{{ route('products.variations.destroy', [$product, $gp->id]) }}" onsubmit="return confirm('{{ __('messages.variation.confirm_remove') ?? 'Retirer ce produit du groupe ?' }}')" style="display: inline-block;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Add product to group button --}}
            <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addVariationModal">
                <i class="bi bi-plus-circle"></i> {{ __('messages.btn.add_variation') }}
            </button>

            {{-- Add product modal --}}
            <div class="modal fade" id="addVariationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="addVariationForm" method="POST" action="{{ route('products.variations.store', $product) }}" onsubmit="return validateAddForm()">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('messages.variation.add_product_to_group') ?? 'Ajouter un produit au groupe' }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                {{-- Product search --}}
                                <div class="mb-3">
                                    <label>{{ __('messages.variation.linked_product') ?? 'Produit' }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="linked_product_search" placeholder="EAN / nom" autocomplete="off">
                                    <input type="hidden" name="linked_product_id" id="linked_product_id" required>
                                    <div id="linked_product_results" class="list-group position-absolute" style="max-height:200px; overflow-y:auto; z-index:1050;"></div>
                                </div>

                                {{-- Attributes for the new product --}}
                                <label class="mb-1">{{ __('messages.variation.attributes') ?? 'Attributs' }} <span class="text-danger">*</span></label>
                                <div id="modalAttributeRows">
                                    <div class="row g-2 mb-2 attr-row">
                                        <div class="col-md-5">
                                            <select class="form-select attr-type-select" name="attributes[0][variation_type_id]" required>
                                                <option value="">-- {{ __('messages.variation.type') ?? 'Type' }} --</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <select class="form-select attr-value-select" name="attributes[0][variation_value_id]" required>
                                                <option value="">-- {{ __('messages.variation.value') ?? 'Valeur' }} --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-attr-row"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="addModalAttrRow"><i class="bi bi-plus"></i> {{ __('messages.variation.add_attribute') ?? 'Ajouter attribut' }}</button>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.cancel') }}</button>
                                <button type="submit" class="btn btn-success">{{ __('messages.btn.add') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        {{-- Barcodes --}}
        <div class="tab-pane fade" id="tab-barcodes" role="tabpanel">
            <h5>{{ __('messages.product.barcodes') ?? 'Codes-barres' }}</h5>
            <p class="text-muted">{{ __('messages.product.barcodes_help') ?? 'Gérez les codes-barres alternatifs pour ce produit. Le code principal est synchronisé avec le champ EAN.' }}</p>

            {{-- Formulaire pour ajouter un barcode --}}
            <form action="{{ route('products.barcodes.store', $product) }}" method="POST" class="row g-2 mb-4">
                @csrf
                <div class="col-md-5">
                    <input type="text" name="barcode" class="form-control" placeholder="{{ __('messages.product.barcode_placeholder') ?? 'Nouveau code-barre' }}" required>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="ean13">EAN-13</option>
                        <option value="ean8">EAN-8</option>
                        <option value="upc">UPC</option>
                        <option value="internal">Interne</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary_barcode" value="1">
                        <label class="form-check-label" for="is_primary_barcode">{{ __('messages.product.primary') ?? 'Principal' }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle"></i> {{ __('messages.btn.add') }}
                    </button>
                </div>
            </form>

            {{-- Liste des barcodes --}}
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.product.barcode') ?? 'Code-barre' }}</th>
                        <th style="width: 120px;">{{ __('messages.product.barcode_type') ?? 'Type' }}</th>
                        <th style="width: 100px;">{{ __('messages.product.primary') ?? 'Principal' }}</th>
                        <th style="width: 150px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->barcodes as $barcode)
                        <tr>
                            <td>
                                <code class="fs-5">{{ $barcode->barcode }}</code>
                                @if($barcode->is_primary)
                                    <span class="badge bg-primary ms-2">{{ __('messages.product.primary') ?? 'Principal' }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ strtoupper($barcode->type) }}</span>
                            </td>
                            <td class="text-center">
                                @if(!$barcode->is_primary)
                                    <form action="{{ route('products.barcodes.setPrimary', [$product, $barcode]) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('messages.product.set_as_primary') ?? 'Définir comme principal' }}">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    </form>
                                @else
                                    <i class="bi bi-star-fill text-warning"></i>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('products.barcodes.destroy', [$product, $barcode]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.product.confirm_delete_barcode') ?? 'Supprimer ce code-barre ?' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" {{ $barcode->is_primary ? 'disabled' : '' }} title="{{ $barcode->is_primary ? __('messages.product.cannot_delete_primary') ?? 'Impossible de supprimer le code principal' : '' }}">
                                        <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center">{{ __('messages.product.no_barcode') ?? 'Aucun code-barre configuré' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- SEO --}}
        <div class="tab-pane fade" id="tab-seo" role="tabpanel">
            <form action="{{ route('products.seo.update', $product) }}" method="POST">
                @csrf @method('PUT')
                <p class="text-muted mb-3">{{ __('messages.product.seo_help') }}</p>
                @php $locales = config('app.website_locales'); $i=0; @endphp
                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $locale)
                        <li class="nav-item">
                            <button class="nav-link @if($i===0) active @endif" data-bs-toggle="tab" data-bs-target="#seo-{{ $locale }}" type="button" role="tab">{{ strtoupper($locale) }}</button>
                        </li>
                        @php $i++; @endphp
                    @endforeach
                </ul>
                <div class="tab-content mt-3">
                    @php $i=0; @endphp
                    @foreach($locales as $locale)
                        <div class="tab-pane fade @if($i===0) show active @endif" id="seo-{{ $locale }}" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.product.seo_title') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" name="seo_title[{{ $locale }}]" class="form-control"
                                       value="{{ old("seo_title.$locale", $product->seo_title[$locale] ?? '') }}"
                                       maxlength="70" placeholder="{{ $product->name[$locale] ?? '' }}">
                                <small class="text-muted">{{ __('messages.product.seo_title_help') }}</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.product.seo_meta_description') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="meta_description[{{ $locale }}]" class="form-control" rows="3"
                                          maxlength="160" placeholder="{{ __('messages.product.seo_meta_placeholder') }}">{{ old("meta_description.$locale", $product->meta_description[$locale] ?? '') }}</textarea>
                                <small class="text-muted">{{ __('messages.product.seo_meta_help') }}</small>
                            </div>

                            {{-- Aperçu Google --}}
                            <div class="card bg-light mb-3">
                                <div class="card-body py-2 px-3">
                                    <small class="text-muted d-block mb-1">{{ __('messages.product.seo_preview') }}</small>
                                    <div style="font-family: Arial, sans-serif;">
                                        <div style="font-size: 18px; color: #1a0dab; margin-bottom: 2px;" id="seo-preview-title-{{ $locale }}">
                                            {{ $product->seo_title[$locale] ?? $product->name[$locale] ?? 'Product Title' }}
                                        </div>
                                        <div style="font-size: 13px; color: #006621; margin-bottom: 2px;">
                                            {{ $product->publicUrl($locale) }}
                                        </div>
                                        <div style="font-size: 13px; color: #545454;" id="seo-preview-desc-{{ $locale }}">
                                            {{ $product->meta_description[$locale] ?? __('messages.product.seo_no_description') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>
                <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
            </form>
        </div>

        <script>
            // Load variation values when a type is selected
            function loadValuesForSelect(typeSelect, valueSelect) {
                let typeId = typeSelect.value;
                if (!typeId) { valueSelect.innerHTML = '<option value="">--</option>'; return; }
                let selectedVal = valueSelect.dataset.selected || '';
                valueSelect.innerHTML = '<option>{{ __('messages.common.loading') ?? 'Chargement...' }}</option>';
                fetch('/variation-types/'+typeId+'/values')
                    .then(res => res.json())
                    .then(data => {
                        valueSelect.innerHTML = '<option value="">--</option>';
                        data.forEach(v => {
                            let opt = document.createElement('option');
                            opt.value = v.id;
                            opt.text = v.value;
                            if (v.id == selectedVal) opt.selected = true;
                            valueSelect.appendChild(opt);
                        });
                        valueSelect.dataset.selected = '';
                    });
            }

            // Bind type→value loading on all .attr-type-select
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('attr-type-select')) {
                    let row = e.target.closest('.attr-row');
                    let valueSelect = row.querySelector('.attr-value-select');
                    loadValuesForSelect(e.target, valueSelect);
                }
            });

            // Initialize existing selects that have a pre-selected type
            document.querySelectorAll('.attr-type-select').forEach(sel => {
                if (sel.value) {
                    let row = sel.closest('.attr-row');
                    let valueSelect = row.querySelector('.attr-value-select');
                    loadValuesForSelect(sel, valueSelect);
                }
            });

            // Remove attribute row
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-attr-row')) {
                    let row = e.target.closest('.attr-row');
                    let container = row.parentElement;
                    if (container.querySelectorAll('.attr-row').length > 1) {
                        row.remove();
                    }
                }
            });

            // Add attribute row (self attributes)
            const typesJson = @json($types);
            function createAttrRow(container) {
                let idx = container.querySelectorAll('.attr-row').length;
                let div = document.createElement('div');
                div.className = 'row g-2 mb-2 attr-row';
                let prefix = container.id === 'modalAttributeRows' ? 'attributes' : 'attributes';
                div.innerHTML = `
                    <div class="col-md-${container.id === 'modalAttributeRows' ? '5' : '4'}">
                        <select class="form-select attr-type-select" name="${prefix}[${idx}][variation_type_id]" required>
                            <option value="">--</option>
                            ${typesJson.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-${container.id === 'modalAttributeRows' ? '5' : '4'}">
                        <select class="form-select attr-value-select" name="${prefix}[${idx}][variation_value_id]" required>
                            <option value="">--</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-attr-row"><i class="bi bi-trash"></i></button>
                    </div>
                `;
                container.appendChild(div);
            }

            document.getElementById('addSelfAttrRow')?.addEventListener('click', () => createAttrRow(document.getElementById('selfAttributeRows')));
            document.getElementById('addModalAttrRow')?.addEventListener('click', () => createAttrRow(document.getElementById('modalAttributeRows')));

            // Product search
            let searchInput = document.getElementById('linked_product_search');
            let resultsDiv = document.getElementById('linked_product_results');
            let hiddenInput = document.getElementById('linked_product_id');
            let searchTimeout = null;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                let q = this.value;
                if (q.length < 2) { resultsDiv.innerHTML = ''; return; }
                searchTimeout = setTimeout(() => {
                    fetch('/products/search?q=' + encodeURIComponent(q))
                        .then(res => res.json())
                        .then(data => {
                            resultsDiv.innerHTML = '';
                            data.forEach(p => {
                                let a = document.createElement('a');
                                a.href = '#';
                                a.className = 'list-group-item list-group-item-action';
                                a.textContent = p.ean + ' - ' + (p.name.fr || Object.values(p.name)[0]);
                                a.dataset.id = p.id;
                                a.addEventListener('click', function(e) {
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

            function validateAddForm() {
                if (!hiddenInput.value) {
                    alert('{{ __("messages.validation.select_linked_product") ?? "Veuillez sélectionner un produit" }}');
                    return false;
                }
                let rows = document.querySelectorAll('#modalAttributeRows .attr-row');
                for (let row of rows) {
                    if (!row.querySelector('.attr-type-select').value || !row.querySelector('.attr-value-select').value) {
                        alert('{{ __("messages.validation.select_variation_type") ?? "Veuillez remplir tous les attributs" }}');
                        return false;
                    }
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
            <input type="number" step="0.00001" name="purchase_price" class="form-control" required>
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
