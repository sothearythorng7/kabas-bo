@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-box-seam"></i> {{ __('messages.factory.raw_materials') }}</h1>

    <div class="d-flex justify-content-between mb-3">
        <a href="{{ route('factory.raw-materials.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.factory.new_material') }}
        </a>

        {{-- Filtres --}}
        <form action="{{ route('factory.raw-materials.index') }}" method="GET" class="d-flex gap-2">
            <select name="supplier_id" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">{{ __('messages.factory.all_suppliers') }}</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            <select name="track_stock" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="">{{ __('messages.factory.all_materials') }}</option>
                <option value="1" {{ request('track_stock') === '1' ? 'selected' : '' }}>{{ __('messages.factory.tracked_only') }}</option>
                <option value="0" {{ request('track_stock') === '0' ? 'selected' : '' }}>{{ __('messages.factory.not_tracked') }}</option>
            </select>
        </form>
    </div>

    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.common.name') }}</th>
                    <th>{{ __('messages.factory.sku') }}</th>
                    <th>{{ __('messages.factory.supplier') }}</th>
                    <th class="text-center">{{ __('messages.factory.unit') }}</th>
                    <th class="text-center">{{ __('messages.factory.track_stock') }}</th>
                    <th class="text-end">{{ __('messages.factory.stock') }}</th>
                    <th class="text-center">{{ __('messages.common.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    <tr class="{{ $material->track_stock && $material->isLowStock() ? 'table-danger' : '' }}">
                        <td style="width: 1%; white-space: nowrap;">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('factory.raw-materials.edit', $material) }}">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('factory.raw-materials.destroy', $material) }}" method="POST" onsubmit="return confirm('{{ __('messages.common.confirm_delete') }}')">
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
                            <a href="{{ route('factory.raw-materials.edit', $material) }}">{{ $material->name }}</a>
                        </td>
                        <td>{{ $material->sku ?? '-' }}</td>
                        <td>{{ $material->supplier?->name ?? '-' }}</td>
                        <td class="text-center">{{ $material->unit }}</td>
                        <td class="text-center">
                            @if($material->track_stock)
                                <i class="bi bi-check-circle text-success"></i>
                            @else
                                <i class="bi bi-x-circle text-muted"></i>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($material->track_stock)
                                {{ number_format($material->total_stock ?? 0, 2) }} {{ $material->unit }}
                                @if($material->isLowStock())
                                    <i class="bi bi-exclamation-triangle text-danger" title="{{ __('messages.factory.low_stock') }}"></i>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($material->is_active)
                                <span class="badge bg-success">{{ __('messages.common.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.common.inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $materials->links() }}
</div>
@endsection
