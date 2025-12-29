@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-building-gear"></i> {{ __('messages.factory.dashboard') }}</h1>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-truck"></i> {{ __('messages.factory.suppliers') }}</h5>
                    <h2 class="mb-0">{{ $stats['suppliers_count'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials') }}</h5>
                    <h2 class="mb-0">{{ $stats['materials_count'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-journal-text"></i> {{ __('messages.factory.recipes') }}</h5>
                    <h2 class="mb-0">{{ $stats['recipes_count'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-gear"></i> {{ __('messages.factory.productions_this_month') }}</h5>
                    <h2 class="mb-0">{{ $stats['productions_this_month'] }}</h2>
                    <small>{{ $stats['units_produced_this_month'] }} {{ __('messages.factory.units') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="btn-group flex-wrap" role="group">
                <a href="{{ route('factory.productions.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> {{ __('messages.factory.new_production') }}
                </a>
                <a href="{{ route('factory.recipes.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-journal-plus"></i> {{ __('messages.factory.new_recipe') }}
                </a>
                <a href="{{ route('factory.raw-materials.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-box-seam"></i> {{ __('messages.factory.new_material') }}
                </a>
                <a href="{{ route('factory.suppliers.create') }}" class="btn btn-outline-primary">
                    <i class="bi bi-truck"></i> {{ __('messages.factory.new_supplier') }}
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Low Stock Alerts --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle"></i> {{ __('messages.factory.low_stock_alerts') }}
                </div>
                <div class="card-body">
                    @if($lowStockMaterials->isEmpty())
                        <p class="text-muted mb-0">{{ __('messages.factory.no_low_stock') }}</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($lowStockMaterials as $material)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('factory.raw-materials.edit', $material) }}">
                                        {{ $material->name }}
                                    </a>
                                    <span class="badge bg-danger rounded-pill">
                                        {{ number_format($material->total_stock, 2) }} {{ $material->unit }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Productions --}}
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> {{ __('messages.factory.recent_productions') }}
                </div>
                <div class="card-body">
                    @if($recentProductions->isEmpty())
                        <p class="text-muted mb-0">{{ __('messages.factory.no_productions') }}</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($recentProductions as $production)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('factory.productions.show', $production) }}">
                                            {{ $production->recipe->product->name[app()->getLocale()] ?? $production->recipe->product->name['en'] ?? '-' }}
                                        </a>
                                        <span class="badge bg-success">{{ $production->quantity_produced }} {{ __('messages.factory.units') }}</span>
                                    </div>
                                    <small class="text-muted">{{ $production->produced_at->format('d/m/Y') }} - {{ $production->user?->name ?? '-' }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Productions --}}
    @if($productionsByRecipe->isNotEmpty())
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart"></i> {{ __('messages.factory.top_productions_this_month') }}
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('messages.factory.product') }}</th>
                                <th class="text-end">{{ __('messages.factory.units_produced') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productionsByRecipe as $item)
                                <tr>
                                    <td>{{ $item->recipe->product->name[app()->getLocale()] ?? $item->recipe->product->name['en'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Raw Materials Stock Overview --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam"></i> {{ __('messages.factory.stock_overview') }}</span>
                    <a href="{{ route('factory.raw-materials.index') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('messages.btn.view_all') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($rawMaterialsStock->isEmpty())
                        <p class="text-muted mb-0">{{ __('messages.factory.no_raw_materials') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>{{ __('messages.common.name') }}</th>
                                        <th>{{ __('messages.factory.sku') }}</th>
                                        <th>{{ __('messages.factory.supplier') }}</th>
                                        <th class="text-end">{{ __('messages.factory.current_stock') }}</th>
                                        <th class="text-end">{{ __('messages.factory.alert_quantity') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rawMaterialsStock as $material)
                                        <tr class="{{ $material['is_low_stock'] ? 'table-warning' : '' }}">
                                            <td>
                                                @if($material['is_low_stock'])
                                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('factory.raw-materials.edit', $material['id']) }}">
                                                    {{ $material['name'] }}
                                                </a>
                                            </td>
                                            <td><small class="text-muted">{{ $material['sku'] ?? '-' }}</small></td>
                                            <td>{{ $material['supplier_name'] ?? '-' }}</td>
                                            <td class="text-end {{ $material['is_low_stock'] ? 'text-danger fw-bold' : '' }}">
                                                {{ number_format($material['total_stock'], 2) }} {{ $material['unit'] }}
                                            </td>
                                            <td class="text-end text-muted">
                                                {{ $material['alert_quantity'] ? number_format($material['alert_quantity'], 2) . ' ' . $material['unit'] : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
