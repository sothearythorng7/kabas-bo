@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.blog_post.title_edit') }}</h1>

    <form action="{{ route('blog.posts.update', $post) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
                                <input type="text" name="title[{{ $locale }}]" class="form-control @error('title.'.$locale) is-invalid @enderror" value="{{ old('title.'.$locale, $post->getTranslation('title', $locale)) }}" required>
                                @error('title.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Extrait --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.excerpt') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="excerpt[{{ $locale }}]" class="form-control @error('excerpt.'.$locale) is-invalid @enderror" rows="3">{{ old('excerpt.'.$locale, $post->getTranslation('excerpt', $locale)) }}</textarea>
                                @error('excerpt.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Contenu --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.content') }} ({{ strtoupper($locale) }}) <span class="text-danger">*</span></label>
                                <textarea name="content[{{ $locale }}]" class="form-control @error('content.'.$locale) is-invalid @enderror" rows="15" required>{{ old('content.'.$locale, $post->getTranslation('content', $locale)) }}</textarea>
                                @error('content.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Meta Title --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.meta_title') }} ({{ strtoupper($locale) }})</label>
                                <input type="text" name="meta_title[{{ $locale }}]" class="form-control @error('meta_title.'.$locale) is-invalid @enderror" value="{{ old('meta_title.'.$locale, $post->getTranslation('meta_title', $locale)) }}">
                                @error('meta_title.'.$locale) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Meta Description --}}
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.blog_post.meta_description') }} ({{ strtoupper($locale) }})</label>
                                <textarea name="meta_description[{{ $locale }}]" class="form-control @error('meta_description.'.$locale) is-invalid @enderror" rows="2">{{ old('meta_description.'.$locale, $post->getTranslation('meta_description', $locale)) }}</textarea>
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
                            <option value="{{ $category->id }}" {{ old('blog_category_id', $post->blog_category_id) == $category->id ? 'selected' : '' }}>
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
                            <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                                {{ $tag->getTranslation('name', 'fr') }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">{{ __('messages.blog_post.tags_help') }}</small>
                    @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Image actuelle --}}
                @if($post->featured_image)
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.current_image') }}</label>
                    <div class="position-relative">
                        <img src="{{ asset('storage/' . $post->featured_image) }}" alt="Featured" class="img-fluid rounded mb-2" style="max-height: 200px;">
                        <form action="{{ route('blog.posts.deleteImage', $post) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('messages.blog_post.delete_image_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i> {{ __('messages.blog_post.delete_image') }}
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- Nouvelle image --}}
                <div class="mb-3">
                    <label class="form-label">
                        @if($post->featured_image)
                            {{ __('messages.blog_post.replace_image') }}
                        @else
                            {{ __('messages.blog_post.featured_image') }}
                        @endif
                    </label>
                    <input type="file" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept="image/*">
                    @error('featured_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Statut --}}
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input" id="is_published" value="1" {{ old('is_published', $post->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">
                            {{ __('messages.blog_post.is_published') }}
                        </label>
                    </div>
                </div>

                {{-- Date de publication --}}
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.blog_post.published_at') }}</label>
                    <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}">
                    @error('published_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Stats --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title">{{ __('messages.blog_post.statistics') }}</h6>
                        <p class="mb-1"><strong>{{ __('messages.blog_post.views_count') }}:</strong> {{ $post->views_count }}</p>
                        <p class="mb-1"><strong>{{ __('messages.blog_post.author_label') }}:</strong> {{ $post->author->name ?? '-' }}</p>
                        <p class="mb-0"><strong>{{ __('messages.blog_post.created_at') }}:</strong> {{ $post->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> {{ __('messages.blog_post.update') }}
            </button>
            <a href="{{ route('blog.posts.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> {{ __('messages.blog_post.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection
