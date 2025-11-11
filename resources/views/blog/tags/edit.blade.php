@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('blog_tag.title_edit')</h1>

    <form action="{{ route('blog.tags.update', $tag) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Onglets par locale --}}
        <ul class="nav nav-tabs" id="tagLocalesTab" role="tablist">
            @foreach($locales as $index => $locale)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if($index===0) active @endif"
                            id="{{ $locale }}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#{{ $locale }}"
                            type="button" role="tab">
                        {{ strtoupper($locale) }}
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="tab-content border border-top-0 p-3 mb-3">
            @foreach($locales as $index => $locale)
                <div class="tab-pane fade @if($index===0) show active @endif" id="{{ $locale }}" role="tabpanel">
                    {{-- Nom --}}
                    <div class="mb-3">
                        <label class="form-label">@t('blog_tag.name') ({{ strtoupper($locale) }}) <span class="text-danger">*</span></label>
                        <input type="text" name="name[{{ $locale }}]" class="form-control @error('name.'.$locale) is-invalid @enderror" value="{{ old('name.'.$locale, $tag->getTranslation('name', $locale)) }}" required>
                        @error('name.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> @t('blog_tag.update')
        </button>
        <a href="{{ route('blog.tags.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> @t('blog_tag.cancel')
        </a>
    </form>
</div>
@endsection
