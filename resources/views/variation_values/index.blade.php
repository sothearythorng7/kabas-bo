@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.variation_value.title') }}</h1>

    <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
        <a href="{{ route('variation-values.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.variation_value.add_value') }}
        </a>

        <form method="GET" action="{{ route('variation-values.index') }}" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0 small">{{ __('messages.variation.type') }}</label>
            <select name="type_id" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                <option value="">{{ __('messages.btn.all') ?? 'Tous' }}</option>
                @foreach($types as $t)
                    <option value="{{ $t->id }}" {{ (string)$typeId === (string)$t->id ? 'selected' : '' }}>{{ $t->label ?: $t->name }}</option>
                @endforeach
            </select>
            @if($typeId)
                <a href="{{ route('variation-values.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i></a>
            @endif
        </form>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th class="text-center">ID</th>
                <th>{{ __('messages.variation.type') }}</th>
                <th>{{ __('messages.variation.value') }}</th>
                <th class="text-center">{{ __('messages.variation_value.products_count') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($values as $value)
            <tr>
                <td style="width: 1%; white-space: nowrap;">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('variation-values.edit', $value) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('variation-values.destroy', $value) }}" method="POST" onsubmit="return confirm('{{ __('messages.variation.confirm_delete') }}')">
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
                <td class="text-center">{{ $value->id }}</td>
                <td>{{ $value->type->name ?? '-' }}</td>
                <td>
                    @if($value->color_hex)
                        <span style="display:inline-block;width:16px;height:16px;border-radius:3px;border:1px solid #ccc;background:{{ $value->color_hex }};vertical-align:middle;margin-right:6px;"></span>
                    @endif
                    {{ $value->value }}
                    @if($value->color_hex)
                        <small class="text-muted">({{ $value->color_hex }})</small>
                    @endif
                </td>
                <td class="text-center">
                    @if($value->products_count > 0)
                        <a href="{{ route('products.index', ['variation_value_id' => $value->id]) }}" class="text-decoration-none" title="{{ __('messages.variation_value.see_products') }}">
                            <span class="badge bg-secondary">{{ $value->products_count }}</span>
                        </a>
                    @else
                        <span class="text-muted small">0</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $values->links() }}
</div>
@endsection
