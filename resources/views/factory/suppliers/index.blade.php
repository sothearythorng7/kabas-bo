@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title"><i class="bi bi-truck"></i> {{ __('messages.factory.suppliers') }}</h1>

    <a href="{{ route('factory.suppliers.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.factory.new_supplier') }}
    </a>

    <div class="table-responsive" style="overflow: visible;">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.common.name') }}</th>
                    <th>{{ __('messages.factory.contact') }}</th>
                    <th class="text-center">{{ __('messages.factory.materials_count') }}</th>
                    <th class="text-center">{{ __('messages.common.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('factory.suppliers.edit', $supplier) }}">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('factory.suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('{{ __('messages.common.confirm_delete') }}')">
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
                        <td>{{ $supplier->name }}</td>
                        <td>
                            @if($supplier->email)
                                <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a><br>
                            @endif
                            @if($supplier->phone)
                                <small class="text-muted">{{ $supplier->phone }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $supplier->raw_materials_count }}</span>
                        </td>
                        <td class="text-center">
                            @if($supplier->is_active)
                                <span class="badge bg-success">{{ __('messages.common.active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.common.inactive') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('messages.common.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $suppliers->links() }}
</div>
@endsection
