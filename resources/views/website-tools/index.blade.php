@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-wrench-adjustable"></i> {{ __('messages.website_tools.title') }}</h1>
    <p class="text-muted mb-4">{{ __('messages.website_tools.description') }}</p>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle"></i> {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Command output --}}
    @if(session('command_output'))
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <i class="bi bi-terminal"></i> {{ __('messages.website_tools.command_output') }}
            </div>
            <div class="card-body p-0">
                <pre class="mb-0 p-3" style="background: #1e1e1e; color: #d4d4d4; max-height: 400px; overflow-y: auto; font-size: 13px; white-space: pre-wrap;">{{ strip_tags(session('command_output')) }}</pre>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Slug Fix Tool --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-link-45deg"></i> {{ __('messages.website_tools.slug_fix_title') }}
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('messages.website_tools.slug_fix_description') }}</p>

                    {{-- Stats --}}
                    <div class="row g-3 mb-4">
                        <div class="col-sm-3">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold text-primary">{{ $totalProducts }}</div>
                                <small class="text-muted">{{ __('messages.website_tools.total_products') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold text-success">{{ $activeProducts }}</div>
                                <small class="text-muted">{{ __('messages.website_tools.active_products') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold {{ $missingSlugCount > 0 ? 'text-danger' : 'text-success' }}">{{ $missingSlugCount }}</div>
                                <small class="text-muted">{{ __('messages.website_tools.missing_slugs') }}</small>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold {{ $emptyNameCount > 0 ? 'text-warning' : 'text-success' }}">{{ $emptyNameCount }}</div>
                                <small class="text-muted">{{ __('messages.website_tools.empty_names') }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="d-flex gap-2">
                        <form action="{{ route('website-tools.fix-slugs') }}" method="POST">
                            @csrf
                            <input type="hidden" name="dry_run" value="1">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search"></i> {{ __('messages.website_tools.dry_run') }}
                            </button>
                        </form>
                        <form action="{{ route('website-tools.fix-slugs') }}" method="POST"
                              onsubmit="return confirm('{{ __('messages.website_tools.confirm_fix') }}')">
                            @csrf
                            <button type="submit" class="btn btn-primary" {{ $missingSlugCount === 0 ? 'disabled' : '' }}>
                                <i class="bi bi-wrench"></i> {{ __('messages.website_tools.fix_now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO Generation Tool --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-search"></i> {{ __('messages.website_tools.seo_title') }}
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('messages.website_tools.seo_description') }}</p>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="border rounded p-3 text-center">
                                <div class="fs-3 fw-bold {{ $missingSeoCount > 0 ? 'text-warning' : 'text-success' }}">{{ $missingSeoCount }}</div>
                                <small class="text-muted">{{ __('messages.website_tools.missing_seo') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <form action="{{ route('website-tools.generate-seo') }}" method="POST">
                            @csrf
                            <input type="hidden" name="dry_run" value="1">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-search"></i> {{ __('messages.website_tools.dry_run') }}
                            </button>
                        </form>
                        <form action="{{ route('website-tools.generate-seo') }}" method="POST"
                              onsubmit="return confirm('{{ __('messages.website_tools.confirm_generate_seo') }}')">
                            @csrf
                            <button type="submit" class="btn btn-primary" {{ $missingSeoCount === 0 ? 'disabled' : '' }}>
                                <i class="bi bi-magic"></i> {{ __('messages.website_tools.generate_seo_now') }}
                            </button>
                        </form>
                        <form action="{{ route('website-tools.generate-seo') }}" method="POST"
                              onsubmit="return confirm('{{ __('messages.website_tools.confirm_regenerate_seo') }}')">
                            @csrf
                            <input type="hidden" name="force" value="1">
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="bi bi-arrow-repeat"></i> {{ __('messages.website_tools.regenerate_all') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Problems list --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="bi bi-exclamation-triangle"></i> {{ __('messages.website_tools.problems') }}
                    @if(count($problems) > 0)
                        <span class="badge bg-danger ms-1">{{ count($problems) }}</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if(count($problems) > 0)
                        <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                            @foreach($problems as $problem)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <a href="{{ route('products.edit', $problem['id']) }}" class="fw-semibold text-decoration-none">
                                                #{{ $problem['id'] }}
                                            </a>
                                            <span class="ms-1">{{ \Illuminate\Support\Str::limit($problem['name'], 30) }}</span>
                                        </div>
                                        <span class="badge bg-{{ $problem['is_active'] ? 'success' : 'secondary' }}">
                                            {{ $problem['is_active'] ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <small class="text-danger">
                                        <i class="bi bi-x-circle"></i>
                                        {{ __('messages.website_tools.slug_missing_for', ['locale' => strtoupper($problem['locale'])]) }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                            {{ __('messages.website_tools.no_problems') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
