@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 crud_title">@t('page.title')</h1>
        <a href="{{ route('admin.pages.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> @t('page.new_page')
        </a>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="text" name="s" value="{{ request('s') }}" class="form-control" placeholder="@t('page.search_placeholder')">
        </div>
        <div class="col-md-3">
            <select name="published" class="form-select">
                <option value="">-- @t('page.publication') --</option>
                <option value="1" @selected(request('published')==='1')>@t('page.published')</option>
                <option value="0" @selected(request('published')==='0')>@t('page.draft')</option>
            </select>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-primary"><i class="bi bi-search"></i> @t('page.search')</button>
        </div>
    </form>

    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>@t('page.title_label') ({{ app()->getLocale() }})</th>
                <th>@t('page.slug')</th>
                <th>@t('page.status')</th>
                <th>@t('page.updated')</th>
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
                        <span class="badge text-bg-success">@t('page.published')</span>
                    @else
                        <span class="badge text-bg-secondary">@t('page.draft')</span>
                    @endif
                </td>
                <td>{{ $p->updated_at?->format('Y-m-d H:i') }}</td>
                <td class="text-end">
                    <a href="{{ route('admin.pages.edit', $p) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.pages.destroy', $p) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('@t('page.delete_confirm')')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.pages.toggle', $p) }}" method="POST" class="d-inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $p->is_published ? 'btn-warning' : 'btn-success' }}">
                            @if($p->is_published)
                                @t('page.unpublish')
                            @else
                                @t('page.publish')
                            @endif
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
