@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-journal-text"></i> {{ $recipe->name }}</h1>
    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('factory.productions.create', ['recipe_id' => $recipe->id]) }}" class="btn btn-success">
            <i class="bi bi-gear"></i> {{ __('messages.factory.start_production') }}
        </a>
        <a href="{{ route('factory.recipes.edit', $recipe) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> {{ __('messages.btn.edit') }}
        </a>
        <form action="{{ route('factory.recipes.clone', $recipe) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-copy"></i> {{ __('messages.factory.clone_recipe') }}
            </button>
        </form>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">{{ __('messages.factory.recipe_details') }}</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('messages.factory.product') }}</dt>
                        <dd class="col-sm-8">
                            @if($recipe->product)
                                <a href="{{ route('products.edit', $recipe->product) }}" target="_blank">
                                    {{ $recipe->product->name[app()->getLocale()] ?? $recipe->product->name['en'] ?? '-' }}
                                </a>
                                <br><small class="text-muted">{{ $recipe->product->ean }}</small>
                            @else
                                <span class="text-danger">{{ __('messages.factory.no_product') }}</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">{{ __('messages.common.status') }}</dt>
                        <dd class="col-sm-8">
                            @if($recipe->is_active)
                                <span class="badge bg-success">{{ __('messages.common.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.common.inactive') }}</span>
                            @endif
                        </dd>

                        @if($recipe->description)
                            <dt class="col-sm-4">{{ __('messages.common.description') }}</dt>
                            <dd class="col-sm-8">{{ $recipe->description }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($recipe->instructions)
            <div class="card mb-3">
                <div class="card-header">{{ __('messages.factory.instructions') }}</div>
                <div class="card-body">
                    {!! nl2br(e($recipe->instructions)) !!}
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('messages.factory.ingredients') }}</span>
                    <span class="badge bg-info">{{ __('messages.factory.max_producible') }}: {{ $recipe->maxProducible() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.factory.material') }}</th>
                                <th class="text-center">{{ __('messages.factory.quantity_per_unit') }}</th>
                                <th class="text-center">{{ __('messages.factory.stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recipe->items as $item)
                                <tr class="{{ $item->rawMaterial->track_stock && $item->rawMaterial->isLowStock() ? 'table-warning' : '' }}">
                                    <td>
                                        <a href="{{ route('factory.raw-materials.edit', $item->rawMaterial) }}">
                                            {{ $item->rawMaterial->name }}
                                        </a>
                                        @if($item->is_optional)
                                            <span class="badge bg-secondary">{{ __('messages.factory.optional') }}</span>
                                        @endif
                                        @if(!$item->rawMaterial->track_stock)
                                            <span class="badge bg-info">{{ __('messages.factory.not_tracked_short') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $item->quantity }} {{ $item->rawMaterial->unit }}
                                    </td>
                                    <td class="text-center">
                                        @if($item->rawMaterial->track_stock)
                                            {{ number_format($item->rawMaterial->total_stock, 2) }} {{ $item->rawMaterial->unit }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Dernières productions --}}
    @if($recipe->productions->isNotEmpty())
    <div class="card">
        <div class="card-header">{{ __('messages.factory.recent_productions') }}</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.common.date') }}</th>
                        <th>{{ __('messages.factory.batch_number') }}</th>
                        <th class="text-center">{{ __('messages.factory.quantity_produced') }}</th>
                        <th>{{ __('messages.common.user') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recipe->productions as $production)
                        <tr>
                            <td>
                                <a href="{{ route('factory.productions.show', $production) }}">
                                    {{ $production->produced_at->format('d/m/Y') }}
                                </a>
                            </td>
                            <td>{{ $production->batch_number ?? '-' }}</td>
                            <td class="text-center">{{ $production->quantity_produced }}</td>
                            <td>{{ $production->user?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('factory.recipes.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>
</div>
@endsection
