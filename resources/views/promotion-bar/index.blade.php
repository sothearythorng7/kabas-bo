@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('Barre de promotion')</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('promotion-bar.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $promotionBar->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        @t('Afficher la barre de promotion')
                    </label>
                    <div class="form-text">@t('Si désactivée, la barre de promotion ne sera pas visible sur le site')</div>
                </div>

                @php $locales = config('app.website_locales', ['en', 'fr']); @endphp

                <ul class="nav nav-tabs" role="tablist">
                    @foreach($locales as $index => $locale)
                        <li class="nav-item">
                            <button class="nav-link @if($index === 0) active @endif"
                                    data-bs-toggle="tab"
                                    data-bs-target="#message-{{ $locale }}"
                                    type="button"
                                    role="tab">
                                {{ strtoupper($locale) }}
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content mt-3">
                    @foreach($locales as $index => $locale)
                        <div class="tab-pane fade @if($index === 0) show active @endif"
                             id="message-{{ $locale }}"
                             role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">
                                    @t('Message de promotion') ({{ strtoupper($locale) }})
                                </label>
                                <input type="text"
                                       name="message[{{ $locale }}]"
                                       class="form-control @error("message.$locale") is-invalid @enderror"
                                       value="{{ old("message.$locale", $promotionBar->getTranslation('message', $locale, false) ?? '') }}"
                                       maxlength="500"
                                       placeholder="@t('Ex: Profitez de 10% de réduction sur les achats de 50$ et plus')">
                                @error("message.$locale")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    @t('Laissez vide pour ne pas afficher la barre de promotion dans cette langue')
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> @t('Enregistrer')
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i>
        <strong>@t('Note :')</strong>
        @t('Si tous les messages sont vides et que la barre est désactivée, elle ne sera pas visible sur le site public.')
    </div>
</div>
@endsection
