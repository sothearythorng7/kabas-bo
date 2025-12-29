@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.variation.types_title') }}</h1>

    <a href="{{ route('variation-types.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.variation.add_type') }}
    </a>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th class="text-center">ID</th>
                <th>{{ __('messages.variation.name') }}</th>
                <th>{{ __('messages.variation.label') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
            <tr>
                <td style="width: 1%; white-space: nowrap;">
                    <div class="dropdown">
                        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('variation-types.edit', $type) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('variation-types.destroy', $type) }}" method="POST" onsubmit="return confirm('{{ __('messages.variation.confirm_delete') }}')">
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
                <td class="text-center">{{ $type->id }}</td>
                <td>{{ $type->name }}</td>
                <td>{{ $type->label }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $types->links() }}
</div>
@endsection
