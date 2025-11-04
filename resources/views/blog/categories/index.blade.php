@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Catégories de Blog</h1>

    <a href="{{ route('blog.categories.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Nouvelle Catégorie
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">ID</th>
                    <th>Nom</th>
                    <th class="text-center">Articles</th>
                    <th class="text-center">Ordre</th>
                    <th class="text-center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td style="width: 1%; white-space: nowrap;" class="text-start">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownCategory{{ $category->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownCategory{{ $category->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('blog.categories.edit', $category) }}">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('blog.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Supprimer cette catégorie ?')">
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
                    <td class="text-center">{{ $category->id }}</td>
                    <td>
                        <strong>{{ $category->getTranslation('name', 'fr') }}</strong>
                        @if($category->getTranslation('description', 'fr'))
                            <br><small class="text-muted">{{ Str::limit($category->getTranslation('description', 'fr'), 80) }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info">{{ $category->posts_count }}</span>
                    </td>
                    <td class="text-center">{{ $category->sort_order }}</td>
                    <td class="text-center">
                        @if($category->is_active)
                            <span class="badge bg-success">Actif</span>
                        @else
                            <span class="badge bg-secondary">Inactif</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $categories->links() }}
</div>
@endsection
