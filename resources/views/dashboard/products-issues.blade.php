@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">@t('dashboard_issues.product_issues_list')</h1>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboard.products-issues') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">@t('dashboard_issues.filter_by_issue')</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="all" {{ $issueType === 'all' ? 'selected' : '' }}>@t('dashboard_issues.all_issues')</option>
                            <option value="no_image" {{ $issueType === 'no_image' ? 'selected' : '' }}>@t('dashboard_issues.no_image')</option>
                            <option value="no_description_fr" {{ $issueType === 'no_description_fr' ? 'selected' : '' }}>@t('dashboard_issues.no_description_fr')</option>
                            <option value="no_description_en" {{ $issueType === 'no_description_en' ? 'selected' : '' }}>@t('dashboard_issues.no_description_en')</option>
                            <option value="fake_or_empty_ean" {{ $issueType === 'fake_or_empty_ean' ? 'selected' : '' }}>@t('dashboard_issues.fake_or_empty_ean')</option>
                            <option value="no_category" {{ $issueType === 'no_category' ? 'selected' : '' }}>@t('dashboard_issues.no_category')</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> @t('menu.back')
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> @t('dashboard_issues.no_products_found')
        </div>
    @else
        <!-- Table desktop -->
        <div class="d-none d-md-block">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>@t('dashboard_issues.product')</th>
                        <th>@t('dashboard_issues.brand')</th>
                        <th>@t('dashboard_issues.issue_type')</th>
                        <th class="text-center">@t('dashboard_issues.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td class="text-muted">{{ $product->id }}</td>
                        <td>
                            <strong>{{ $product->name['fr'] ?? $product->name['en'] ?? 'N/A' }}</strong><br>
                            <small class="text-muted">EAN: {{ $product->ean }}</small>
                        </td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>
                        <td>
                            @foreach($product->issues as $issue)
                                @if($issue === 'no_image')
                                    <span class="badge bg-warning text-dark mb-1">
                                        <i class="bi bi-image"></i> @t('dashboard_issues.no_image')
                                    </span><br>
                                @elseif($issue === 'no_description_fr')
                                    <span class="badge bg-danger mb-1">
                                        <i class="bi bi-file-text"></i> @t('dashboard_issues.no_description_fr')
                                    </span><br>
                                @elseif($issue === 'no_description_en')
                                    <span class="badge bg-info mb-1">
                                        <i class="bi bi-file-text"></i> @t('dashboard_issues.no_description_en')
                                    </span><br>
                                @elseif($issue === 'fake_or_empty_ean')
                                    <span class="badge bg-warning text-dark mb-1">
                                        <i class="bi bi-upc-scan"></i> @t('dashboard_issues.fake_or_empty_ean')
                                    </span><br>
                                @elseif($issue === 'no_category')
                                    <span class="badge bg-primary mb-1">
                                        <i class="bi bi-bookmarks"></i> @t('dashboard_issues.no_category')
                                    </span><br>
                                @endif
                            @endforeach
                        </td>
                        <td class="text-center">
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> @t('dashboard_issues.edit')
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Cards mobile -->
        <div class="d-md-none">
            @foreach($products as $product)
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">{{ $product->name['fr'] ?? $product->name['en'] ?? 'N/A' }}</h5>
                    <p class="card-text">
                        <small class="text-muted">ID: {{ $product->id }} | EAN: {{ $product->ean }}</small><br>
                        <strong>@t('dashboard_issues.brand'):</strong> {{ $product->brand?->name ?? '-' }}<br>
                        <strong>@t('dashboard_issues.issue_type'):</strong><br>
                        @foreach($product->issues as $issue)
                            @if($issue === 'no_image')
                                <span class="badge bg-warning text-dark mb-1">
                                    <i class="bi bi-image"></i> @t('dashboard_issues.no_image')
                                </span><br>
                            @elseif($issue === 'no_description_fr')
                                <span class="badge bg-danger mb-1">
                                    <i class="bi bi-file-text"></i> @t('dashboard_issues.no_description_fr')
                                </span><br>
                            @elseif($issue === 'no_description_en')
                                <span class="badge bg-info mb-1">
                                    <i class="bi bi-file-text"></i> @t('dashboard_issues.no_description_en')
                                </span><br>
                            @elseif($issue === 'fake_or_empty_ean')
                                <span class="badge bg-warning text-dark mb-1">
                                    <i class="bi bi-upc-scan"></i> @t('dashboard_issues.fake_or_empty_ean')
                                </span><br>
                            @elseif($issue === 'no_category')
                                <span class="badge bg-primary mb-1">
                                    <i class="bi bi-bookmarks"></i> @t('dashboard_issues.no_category')
                                </span><br>
                            @endif
                        @endforeach
                    </p>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> @t('dashboard_issues.edit')
                    </a>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
