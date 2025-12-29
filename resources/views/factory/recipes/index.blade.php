@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-journal-text"></i> {{ __('messages.factory.recipes') }}</h1>

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('factory.recipes.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.factory.new_recipe') }}
        </a>

        {{-- Recherche --}}
        <form action="{{ route('factory.recipes.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" style="width: 200px;" placeholder="{{ __('messages.common.search') }}..." value="{{ request('q') }}">
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>

    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.factory.recipe_name') }}</th>
                    <th>{{ __('messages.factory.product') }}</th>
                    <th class="text-center">{{ __('messages.factory.ingredients') }}</th>
                    <th class="text-center">{{ __('messages.factory.production_capacity') }}</th>
                    <th class="text-center">{{ __('messages.common.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recipes as $recipe)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('factory.recipes.show', $recipe) }}">
                                            <i class="bi bi-eye"></i> {{ __('messages.btn.view') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('factory.recipes.edit', $recipe) }}">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-success" href="{{ route('factory.productions.create', ['recipe_id' => $recipe->id]) }}">
                                            <i class="bi bi-gear"></i> {{ __('messages.factory.start_production') }}
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('factory.recipes.destroy', $recipe) }}" method="POST" onsubmit="return confirm('{{ __('messages.common.confirm_delete') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">
                                                <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('factory.recipes.show', $recipe) }}">{{ $recipe->name }}</a>
                        </td>
                        <td>
                            @if($recipe->product)
                                <a href="{{ route('products.edit', $recipe->product) }}" target="_blank">
                                    {{ $recipe->product->name[app()->getLocale()] ?? $recipe->product->name['en'] ?? '-' }}
                                </a>
                                <br><small class="text-muted">{{ $recipe->product->ean }}</small>
                            @else
                                <span class="text-danger">{{ __('messages.factory.no_product') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $recipe->items->count() }}</span>
                        </td>
                        <td class="text-center">
                            @php
                                $maxProducible = $recipe->maxProducible();
                                $hasTrackedIngredients = $recipe->items->contains(fn($item) => !$item->is_optional && $item->rawMaterial->track_stock);
                            @endphp
                            @if(!$hasTrackedIngredients)
                                <span class="badge bg-secondary" data-bs-toggle="tooltip" title="{{ __('messages.factory.no_tracked_ingredients') }}">
                                    {{ __('messages.factory.unlimited') }}
                                </span>
                            @elseif($maxProducible === 0)
                                <span class="badge bg-danger">0</span>
                            @elseif($maxProducible < 10)
                                <span class="badge bg-warning text-dark">{{ $maxProducible }}</span>
                            @else
                                <span class="badge bg-success">{{ $maxProducible }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($recipe->is_active)
                                <span class="badge bg-success">{{ __('messages.common.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.common.inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $recipes->links() }}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
