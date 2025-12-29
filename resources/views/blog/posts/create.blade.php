@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.blog_post.title_create') }}</h1>

    <form action="{{ route('blog.posts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-md-8">
                {{-- Onglets par locale --}}
                <ul class="nav nav-tabs" id="postLocalesTab" role="tablist">
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
                            {{-- Titre --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.title_label') }} ({{ strtoupper($locale) }}) <span class="text-danger">*</span></label>
                                <input type="text" name="title[{{ $locale }}]" class="form-control @error('title.'.$locale) is-invalid @enderror" value="{{ old('title.'.$locale) }}" required>
                                @error('title.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Extrait --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.excerpt') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="excerpt[{{ $locale }}]" class="form-control @error('excerpt.'.$locale) is-invalid @enderror" rows="3">{{ old('excerpt.'.$locale) }}</textarea>
                                @error('excerpt.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Contenu --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.content') }} ({{ strtoupper($locale) }}) <span class="text-danger">*</span></label>
                                <textarea name="content[{{ $locale }}]" class="form-control @error('content.'.$locale) is-invalid @enderror" rows="15" required>{{ old('content.'.$locale) }}</textarea>
                                @error('content.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Meta Title --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.meta_title') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" name="meta_title[{{ $locale }}]" class="form-control @error('meta_title.'.$locale) is-invalid @enderror" value="{{ old('meta_title.'.$locale) }}">
                                @error('meta_title.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Meta Description --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.meta_description') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="meta_description[{{ $locale }}]" class="form-control @error('meta_description.'.$locale) is-invalid @enderror" rows="2">{{ old('meta_description.'.$locale) }}</textarea>
                                @error('meta_description.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-md-4">
                {{-- Catégorie --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.category') }}</label>
                    <select name="blog_category_id" class="form-select @error('blog_category_id') is-invalid @enderror">
                        <option value="">{{ __('messages.blog_post.none') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('blog_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->getTranslation('name', 'fr') }}
                            </option>
                        @endforeach
                    </select>
                    @error('blog_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Tags --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.tags') }}</label>
                    <select name="tags[]" class="form-select @error('tags') is-invalid @enderror" multiple size="6">
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>
                                {{ $tag->getTranslation('name', 'fr') }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">{{ __('messages.blog_post.tags_help') }}</small>
                    @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Image --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.featured_image') }}</label>
                    <input type="file" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/*">
                    @error('featured_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Statut --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">
                            {{ __('messages.blog_post.publish_immediately') }}
                        </label>
                    </div>
                </div>

                {{-- Date de publication --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.published_at') }}</label>
                    <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at') }}">
                    @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="form-text text-muted">{{ __('messages.blog_post.published_at_help') }}</small>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> {{ __('messages.blog_post.save') }}
            </button>
            <a href="{{ route('blog.posts.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.blog_post.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
