@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Articles de Blog</h1>

    <a href="{{ route('blog.posts.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Nouvel Article
    </a>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('blog.posts.index') }}" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">-- Toutes les catégories --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->getTranslation('name', 'fr') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">-- Tous les statuts --</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Publiés</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillons</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i> Filtrer
            </button>
        </div>
    </form>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">ID</th>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Auteur</th>
                    <th class="text-center">Statut</th>
                    <th class="text-center">Vues</th>
                    <th>Date</th>
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
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ $post->publicUrl('fr') }}" target="_blank">
                                        <i class="bi bi-eye-fill"></i> Voir
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('blog.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Supprimer cet article ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="bi bi-trash-fill"></i> Supprimer
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
                            <span class="badge bg-success">Publié</span>
                        @else
                            <span class="badge bg-warning">Brouillon</span>
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
