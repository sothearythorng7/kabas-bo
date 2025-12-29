@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h1 class="crud_title">
            <i class="bi bi-gear"></i> {{ __('messages.factory.production') }} #{{ $production->id }}
        </h1>
        @if($production->created_at->diffInHours(now()) <= 24)
        <form action="{{ route('factory.productions.destroy', $production) }}" method="POST" onsubmit="return confirm('{{ __('messages.factory.confirm_delete_production') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash"></i> {{ __('messages.btn.delete') }}
            </button>
        </form>
        @endif
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">{{ __('messages.factory.production_details') }}</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">{{ __('messages.common.date') }}</dt>
                        <dd class="col-sm-8">{{ $production->produced_at->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">{{ __('messages.factory.recipe') }}</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('factory.recipes.show', $production->recipe) }}">
                                {{ $production->recipe->name }}
                            </a>
                        </dd>

                        <dt class="col-sm-4">{{ __('messages.factory.product') }}</dt>
                        <dd class="col-sm-8">
                            @if($production->recipe->product)
                                <a href="{{ route('products.edit', $production->recipe->product) }}" target="_blank">
                                    {{ $production->recipe->product->name[app()->getLocale()] ?? $production->recipe->product->name['en'] ?? '-' }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">{{ __('messages.factory.quantity_produced') }}</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-success fs-5">{{ $production->quantity_produced }}</span>
                        </dd>

                        <dt class="col-sm-4">{{ __('messages.factory.batch_number') }}</dt>
                        <dd class="col-sm-8">{{ $production->batch_number ?? '-' }}</dd>

                        <dt class="col-sm-4">{{ __('messages.common.user') }}</dt>
                        <dd class="col-sm-8">{{ $production->user?->name ?? '-' }}</dd>

                        <dt class="col-sm-4">{{ __('messages.common.created_at') }}</dt>
                        <dd class="col-sm-8">{{ $production->created_at->format('d/m/Y H:i') }}</dd>

                        @if($production->notes)
                            <dt class="col-sm-4">{{ __('messages.common.notes') }}</dt>
                            <dd class="col-sm-8">{{ $production->notes }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">{{ __('messages.factory.materials_consumed') }}</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.factory.material') }}</th>
                                <th class="text-end">{{ __('messages.factory.quantity_consumed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($production->consumptions as $consumption)
                                <tr>
                                    <td>
                                        <a href="{{ route('factory.raw-materials.edit', $consumption->rawMaterial) }}">
                                            {{ $consumption->rawMaterial->name }}
                                        </a>
                                        @if(!$consumption->rawMaterial->track_stock)
                                            <span class="badge bg-info">{{ __('messages.factory.not_tracked_short') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($consumption->quantity_consumed, 4) }} {{ $consumption->rawMaterial->unit }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Stock créé --}}
            @if($production->stockBatches->isNotEmpty())
            <div class="card">
                <div class="card-header">{{ __('messages.factory.stock_created') }}</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.factory.store') }}</th>
                                <th class="text-end">{{ __('messages.factory.quantity') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($production->stockBatches as $batch)
                                <tr>
                                    <td>{{ $batch->store?->name ?? '-' }}</td>
                                    <td class="text-end">{{ $batch->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('factory.productions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
        <a href="{{ route('factory.productions.create', ['recipe_id' => $production->recipe_id]) }}" class="btn btn-success">
            <i class="bi bi-arrow-repeat"></i> {{ __('messages.factory.produce_again') }}
        </a>
    </div>
</div>
@endsection
