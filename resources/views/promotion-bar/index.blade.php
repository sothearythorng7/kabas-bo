@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.promotion_bar.title') }}</h1>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('promotion-bar.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                        {{ old('is_active', $promotionBar->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        {{ __('messages.promotion_bar.show_promotion_bar') }}
                    </label>
                    <div class="form-text">{{ __('messages.promotion_bar.disabled_hint') }}</div>
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
                                    {{ __('messages.promotion_bar.message') }} ({{ strtoupper($locale) }})
                                </label>
                                <input type="text"
                                       name="message[{{ $locale }}]"
                                       class="form-control @error("message.$locale") is-invalid @enderror"
                                       value="{{ old("message.$locale", $promotionBar->getTranslation('message', $locale, false) ?? '') }}"
                                       maxlength="500"
                                       placeholder="{{ __('messages.promotion_bar.placeholder_example') }}">
                                @error("message.$locale")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    {{ __('messages.promotion_bar.leave_empty_hint') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> {{ __('messages.promotion_bar.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle"></i>
        <strong>{{ __('messages.promotion_bar.note') }}</strong>
        {{ __('messages.promotion_bar.all_empty_hint') }}
    </div>
</div>
@endsection
