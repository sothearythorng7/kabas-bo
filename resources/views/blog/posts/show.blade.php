@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">@t('blog_post.title_show')</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('blog.posts.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> @t('blog_post.back_to_list')
            </a>
            <a href="{{ route('blog.posts.edit', $post) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> @t('blog_post.edit')
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {{-- Statut --}}
            <div class="mb-3">
                <strong>@t('blog_post.status'):</strong>
                @if($post->is_published)
                    <span class="badge bg-success">@t('blog_post.published')</span>
                @else
                    <span class="badge bg-warning">@t('blog_post.draft')</span>
                @endif
            </div>

            {{-- Date de publication --}}
            @if($post->published_at)
            <div class="mb-3">
                <strong>@t('blog_post.published_date'):</strong>
                {{ $post->published_at->format('d/m/Y H:i') }}
            </div>
            @endif

            {{-- Catégorie --}}
            @if($post->category)
            <div class="mb-3">
                <strong>@t('blog_post.category'):</strong>
                {{ $post->category->name['fr'] ?? $post->category->name['en'] ?? '-' }}
            </div>
            @endif

            {{-- Auteur --}}
            <div class="mb-3">
                <strong>@t('blog_post.author'):</strong>
                {{ $post->author->name ?? '-' }}
            </div>

            {{-- Nombre de vues --}}
            <div class="mb-3">
                <strong>@t('blog_post.views'):</strong>
                {{ $post->views_count }}
            </div>

            {{-- Tags --}}
            @if($post->tags->count() > 0)
            <div class="mb-3">
                <strong>@t('blog_post.tags'):</strong>
                @foreach($post->tags as $tag)
                    <span class="badge bg-secondary">{{ $tag->name['fr'] ?? $tag->name['en'] ?? '' }}</span>
                @endforeach
            </div>
            @endif

            {{-- Image --}}
            @if($post->featured_image)
            <div class="mb-3">
                <strong>@t('blog_post.featured_image'):</strong>
                <div class="mt-2">
                    <img src="{{ asset('storage/' . $post->featured_image) }}" alt="Featured" class="img-fluid" style="max-width: 500px;">
                </div>
            </div>
            @endif

            {{-- Onglets par locale --}}
            <ul class="nav nav-tabs mt-4" id="postLocalesTab" role="tablist">
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

            <div class="tab-content border border-top-0 p-3">
                @foreach($locales as $index => $locale)
                    <div class="tab-pane fade @if($index===0) show active @endif" id="{{ $locale }}" role="tabpanel">
                        {{-- Titre --}}
                        <div class="mb-3">
                            <strong>@t('blog_post.title_label'):</strong>
                            <div>{{ $post->title[$locale] ?? '-' }}</div>
                        </div>

                        {{-- Slug --}}
                        <div class="mb-3">
                            <strong>@t('blog_post.slug'):</strong>
                            <div><code>{{ $post->slug[$locale] ?? '-' }}</code></div>
                        </div>

                        {{-- URL publique --}}
                        <div class="mb-3">
                            <strong>@t('blog_post.public_url'):</strong>
                            <div>
                                @if(!empty($post->slug[$locale]))
                                    <a href="{{ $post->publicUrl($locale) }}" target="_blank">
                                        {{ $post->publicUrl($locale) }}
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        {{-- Excerpt --}}
                        <div class="mb-3">
                            <strong>@t('blog_post.excerpt'):</strong>
                            <div>{{ $post->excerpt[$locale] ?? '-' }}</div>
                        </div>

                        {{-- Contenu --}}
                        <div class="mb-3">
                            <strong>@t('blog_post.content'):</strong>
                            <div class="border p-3 bg-light">
                                {!! $post->content[$locale] ?? '-' !!}
                            </div>
                        </div>

                        {{-- Meta Title --}}
                        @if(!empty($post->meta_title[$locale]))
                        <div class="mb-3">
                            <strong>@t('blog_post.meta_title'):</strong>
                            <div>{{ $post->meta_title[$locale] }}</div>
                        </div>
                        @endif

                        {{-- Meta Description --}}
                        @if(!empty($post->meta_description[$locale]))
                        <div class="mb-3">
                            <strong>@t('blog_post.meta_description'):</strong>
                            <div>{{ $post->meta_description[$locale] }}</div>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
