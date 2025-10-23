@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Pages</h1>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nouvelle page
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="s" value="{{ request('s') }}" class="form-control" placeholder="Rechercher titre/slug">
        </div>
        <div class="col-md-3">
            <select name="published" class="form-select">
                <option value="">-- Publication --</option>
                <option value="1" @selected(request('published')==='1')>Publiée</option>
                <option value="0" @selected(request('published')==='0')>Brouillon</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
        </div>
    </form>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Titre ({{ app()->getLocale() }})</th>
                <th>Slug</th>
                <th>Statut</th>
                <th>MAJ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($pages as $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->title[app()->getLocale()] ?? reset($p->title) }}</td>
                <td>{{ $p->slugs[app()->getLocale()] ?? reset($p->slugs) }}</td>
                <td>
                    @if($p->is_published)
                        <span class="badge text-bg-success">Publié</span>
                    @else
                        <span class="badge text-bg-secondary">Brouillon</span>
                    @endif
                </td>
                <td>{{ $p->updated_at?->format('Y-m-d H:i') }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.pages.edit', $p) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.pages.destroy', $p) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Supprimer cette page ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.pages.toggle', $p) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm {{ $p->is_published ? 'btn-warning' : 'btn-success' }}">
                            {{ $p->is_published ? 'Dépublier' : 'Publier' }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $pages->links() }}
</div>
@endsection
