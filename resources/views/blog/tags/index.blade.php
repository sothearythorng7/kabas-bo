@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">Tags de Blog</h1>

    <a href="{{ route('blog.tags.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> Nouveau Tag
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">ID</th>
                    <th>Nom</th>
                    <th class="text-center">Articles</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                <tr>
                    <td style="width: 1%; white-space: nowrap;" class="text-start">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownTag{{ $tag->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownTag{{ $tag->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('blog.tags.edit', $tag) }}">
                                        <i class="bi bi-pencil-fill"></i> Modifier
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('blog.tags.destroy', $tag) }}" method="POST" onsubmit="return confirm('Supprimer ce tag ?')">
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
                    <td class="text-center">{{ $tag->id }}</td>
                    <td>
                        <span class="badge bg-primary">{{ $tag->getTranslation('name', 'fr') }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-info">{{ $tag->posts_count }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $tags->links() }}
</div>
@endsection
