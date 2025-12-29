@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.blog_post.title') }}</h1>

    <a href="{{ route('blog.posts.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.blog_post.new_article') }}
    </a>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('blog.posts.index') }}" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="{{ __('messages.blog_post.search_placeholder') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">{{ __('messages.blog_post.all_categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->getTranslation('name', 'fr') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">{{ __('messages.blog_post.all_statuses') }}</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>{{ __('messages.blog_post.published') }}</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('messages.blog_post.draft') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> {{ __('messages.blog_post.filter') }}
            </button>
        </div>
    </form>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">{{ __('messages.blog_post.id') }}</th>
                    <th>{{ __('messages.blog_post.title_label') }}</th>
                    <th>{{ __('messages.blog_post.category') }}</th>
                    <th>{{ __('messages.blog_post.author') }}</th>
                    <th class="text-center">{{ __('messages.blog_post.status') }}</th>
                    <th class="text-center">{{ __('messages.blog_post.views') }}</th>
                    <th>{{ __('messages.blog_post.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posts as $post)
                <tr>
                    <td style="width: 1%; white-space: nowrap;" class="text-start">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownPost{{ $post->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownPost{{ $post->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('blog.posts.edit', $post) }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.blog_post.edit') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('blog.posts.show', $post) }}">
                                        <i class="bi bi-eye-fill"></i> {{ __('messages.blog_post.view') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ $post->publicUrl('fr') }}" target="_blank">
                                        <i class="bi bi-box-arrow-up-right"></i> {{ __('messages.blog_post.view_on_site') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('blog.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('{{ __('messages.blog_post.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash-fill"></i> {{ __('messages.blog_post.delete') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                    <td class="text-center">{{ $post->id }}</td>
                    <td>
                        <strong>{{ $post->getTranslation('title', 'fr') }}</strong>
                        @if($post->featured_image)
                            <i class="bi bi-image text-primary ms-2"></i>
                        @endif
                    </td>
                    <td>
                        @if($post->category)
                            <span class="badge bg-secondary">{{ $post->category->getTranslation('name', 'fr') }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $post->author->name ?? '-' }}</td>
                    <td class="text-center">
                        @if($post->is_published)
                            <span class="badge bg-success">{{ __('messages.blog_post.published') }}</span>
                        @else
                            <span class="badge bg-warning">{{ __('messages.blog_post.draft') }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $post->views_count }}</td>
                    <td>{{ $post->published_at ? $post->published_at->format('d/m/Y') : '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $posts->links() }}
</div>
@endsection
