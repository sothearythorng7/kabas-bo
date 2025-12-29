@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-gear"></i> {{ __('messages.factory.productions') }}</h1>

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('factory.productions.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.factory.new_production') }}
        </a>

        {{-- Filtres --}}
        <form action="{{ route('factory.productions.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
            <select name="recipe_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">{{ __('messages.factory.all_recipes') }}</option>
                @foreach($recipes as $recipe)
                    <option value="{{ $recipe->id }}" {{ request('recipe_id') == $recipe->id ? 'selected' : '' }}>
                        {{ $recipe->name }}
                    </option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="form-control form-control-sm" style="width: auto;" value="{{ request('date_from') }}" onchange="this.form.submit()">
            <input type="date" name="date_to" class="form-control form-control-sm" style="width: auto;" value="{{ request('date_to') }}" onchange="this.form.submit()">
        </form>
    </div>

    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.common.date') }}</th>
                    <th>{{ __('messages.factory.recipe') }}</th>
                    <th>{{ __('messages.factory.product') }}</th>
                    <th class="text-center">{{ __('messages.factory.quantity_produced') }}</th>
                    <th>{{ __('messages.factory.batch_number') }}</th>
                    <th>{{ __('messages.common.user') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productions as $production)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('factory.productions.show', $production) }}">
                                            <i class="bi bi-eye"></i> {{ __('messages.btn.view') }}
                                        </a>
                                    </li>
                                    @if($production->created_at->diffInHours(now()) <= 24)
                                    <li>
                                        <form action="{{ route('factory.productions.destroy', $production) }}" method="POST" onsubmit="return confirm('{{ __('messages.factory.confirm_delete_production') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit">
                                                <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('factory.productions.show', $production) }}">
                                {{ $production->produced_at->format('d/m/Y') }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('factory.recipes.show', $production->recipe) }}">
                                {{ $production->recipe->name }}
                            </a>
                        </td>
                        <td>
                            @if($production->recipe->product)
                                {{ $production->recipe->product->name[app()->getLocale()] ?? $production->recipe->product->name['en'] ?? '-' }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success fs-6">{{ $production->quantity_produced }}</span>
                        </td>
                        <td>{{ $production->batch_number ?? '-' }}</td>
                        <td>{{ $production->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $productions->links() }}
</div>
@endsection
