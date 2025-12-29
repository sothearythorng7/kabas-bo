@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.supplier.title') }}</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('suppliers.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier.btnCreate') }}
        </a>
        <form method="GET" action="{{ route('suppliers.index') }}" class="d-flex align-items-center gap-2">
            <input type="text" name="search" class="form-control" placeholder="{{ __('messages.supplier.search_placeholder') }}" value="{{ request('search') }}" style="width: 250px;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
            @if(request('search'))
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th> {{-- Dropdown actions --}}
                    <th class="text-center">ID</th>
                    <th>{{ __('messages.supplier.name') }}</th>
                    <th>{{ __('messages.supplier.address') }}</th>
                    <th>{{ __('messages.supplier.type') }}</th> {{-- Nouveau --}}
                </tr>
            </thead>
            <tbody>
                @foreach($suppliers as $supplier)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;" class="text-start">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownSupplier{{ $supplier->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownSupplier{{ $supplier->id }}">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('suppliers.edit', $supplier) }}">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" onsubmit="return confirm('{{ __('messages.supplier.confirm_delete') }}')">
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
                        <td class="text-center">{{ $supplier->id }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->address }}</td>
                        <td>
                            @if($supplier->type === 'buyer')
                                <span class="badge bg-success">{{ __('messages.supplier.type_buyer') }}</span>
                            @elseif($supplier->type === 'consignment')
                                <span class="badge bg-info">{{ __('messages.supplier.type_consignment') }}</span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $suppliers->links() }}
</div>
@endsection
