<form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">EAN</label>
            <input type="text" name="ean" class="form-control @error('ean') is-invalid @enderror" value="{{ old('ean', $product->ean) }}" required>
            @error('ean') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Brand</label>
            <select name="brand_id" class="form-select">
                <option value="">--</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id)==$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Price</label>
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
                    <label class="form-label">Name ({{ strtoupper($locale) }})</label>
                    <input type="text" name="name[{{ $locale }}]" class="form-control"
                           value="{{ old("name.$locale", $product->name[$locale] ?? '') }}" required>
                </div>
            </div>
            @php $i++; @endphp
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Color</label>
            <input type="text" name="color" class="form-control" value="{{ old('color', $product->color) }}">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Size</label>
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

    <div class="mt-3">
        <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
    </div>
</form>
