@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.product.btnCreate') }}</h1>
    <a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">
        <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back_to_list') }}
    </a>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="tab-content mt-3">
            {{-- General --}}
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@t("product.ean")</label>
                        <div class="input-group">
                            <input type="text" name="ean" id="ean-input" class="form-control @error('ean') is-invalid @enderror" value="{{ old('ean') }}" required>
                            <button type="button" class="btn btn-outline-secondary" id="generate-ean-btn" title="Générer un EAN fake">
                                <i class="bi bi-shuffle"></i>
                            </button>
                        </div>
                        @error('ean') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@t("product.brand_label")</label>
                        <select name="brand_id" class="form-select">
                            <option value="">--</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" @selected(old('brand_id')==$b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@t("product.price")</label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', 0) }}">
                        @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">@t("product.price_btob")</label>
                        <input type="number" step="0.01" name="price" class="form-control @error('price_btob') is-invalid @enderror" value="{{ old('price_btob', 0) }}">
                        @error('price_btob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                {{-- Name per locale --}}
                @php $i=0; @endphp
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
                                <label class="form-label">@t("product.name") ({{ strtoupper($locale) }})
                                    @if($locale === 'en')
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>
                                <input type="text" name="name[{{ $locale }}]" class="form-control" value="{{ old("name.$locale") }}" {{ $locale === 'en' ? 'required' : '' }}>
                            </div>
                        </div>
                        @php $i++; @endphp
                    @endforeach
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">@t("active")</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_best_seller" id="is_best_seller" value="1" {{ old('is_best_seller', false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_best_seller">@t("Best seller")</label>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="allow_overselling" id="allow_overselling" value="1" {{ old('allow_overselling', false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_overselling">@t("product.allow_overselling")</label>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-success">{{ __('messages.btn.save') }}</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">{{ __('messages.btn.cancel') }}</a>
        </div>
    </form>
</div>

<script>
document.getElementById('generate-ean-btn').addEventListener('click', function() {
    // Générer un numéro aléatoire de 8 chiffres
    const randomNumber = Math.floor(10000000 + Math.random() * 90000000);
    const fakeEan = 'FAKE-' + randomNumber;

    // Vérifier si cet EAN existe déjà via AJAX
    fetch('/products/check-ean?ean=' + encodeURIComponent(fakeEan))
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                // Si l'EAN existe déjà, générer un nouveau
                document.getElementById('generate-ean-btn').click();
            } else {
                // Insérer l'EAN dans le champ
                document.getElementById('ean-input').value = fakeEan;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification de l\'EAN:', error);
            // En cas d'erreur, on met quand même l'EAN généré
            document.getElementById('ean-input').value = fakeEan;
        });
});
</script>
@endsection
