@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.store.title') }}</h1>
    
    <a href="{{ route('stores.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.store.btnCreate') }}
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th> {{-- Dropdown actions --}}
                    <th class="text-center">ID</th>
                    <th>{{ __('messages.store.name') }}</th>
                    <th>{{ __('messages.store.is_reseller') }}</th>
                    <th>{{ __('messages.store.address') }}</th>
                    <th>{{ __('messages.store.phone') }}</th>
                    <th>{{ __('messages.store.email') }}</th>
                    <th>{{ __('messages.store.opening_time') }}</th>
                    <th>{{ __('messages.store.closing_time') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stores as $store)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;" class="text-start">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownStore{{ $store->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownStore{{ $store->id }}">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('stores.edit', $store) }}">
                                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <form action="{{ route('stores.destroy', $store) }}" method="POST" onsubmit="return confirm('{{ __('messages.store.confirm_delete') }}')">
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
                        <td class="text-center">{{ $store->id }}</td>
                        <td>{{ $store->name }}</td>
                        <td>
                            @if($store->is_reseller)
                                <span class="badge bg-success">{{ __('messages.yes') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('messages.no') }}</span>
                            @endif
                        </td>
                        <td>{{ $store->address }}</td>
                        <td>{{ $store->phone }}</td>
                        <td>{{ $store->email }}</td>
                        <td>{{ $store->opening_time ?? '-' }}</td>
                        <td>{{ $store->closing_time ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $stores->links() }}
</div>
@endsection
