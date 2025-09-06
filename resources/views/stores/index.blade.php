@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.store.title') }}</h1>
    
    <a href="{{ route('stores.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.store.btnCreate') }}
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('messages.store.name') }}</th>
                    <th>{{ __('messages.store.address') }}</th>
                    <th>{{ __('messages.store.phone') }}</th>
                    <th>{{ __('messages.store.email') }}</th>
                    <th>{{ __('messages.store.opening_time') }}</th>
                    <th>{{ __('messages.store.closing_time') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($stores as $store)
                    <tr>
                        <td>{{ $store->id }}</td>
                        <td>{{ $store->name }}</td>
                        <td>{{ $store->address }}</td>
                        <td>{{ $store->phone }}</td>
                        <td>{{ $store->email }}</td>
                        <td>{{ $store->opening_time ?? '-' }}</td>
                        <td>{{ $store->closing_time ?? '-' }}</td>
                        <td class="d-flex justify-content-end gap-1">
                            <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                            </a>
                            <form action="{{ route('stores.destroy', $store) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('{{ __('messages.store.confirm_delete') }}')">
                                    <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile view --}}
    <div class="d-md-none">
        <div class="row">
            @foreach($stores as $store)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-1">{{ $store->name }}</h5>
                            <p class="card-text mb-1"><strong>{{ __('messages.store.address') }}:</strong> {{ $store->address }}</p>
                            <p class="card-text mb-1"><strong>{{ __('messages.store.phone') }}:</strong> {{ $store->phone }}</p>
                            <p class="card-text mb-1"><strong>{{ __('messages.store.email') }}:</strong> {{ $store->email }}</p>
                            <p class="card-text mb-1"><strong>{{ __('messages.store.opening_time') }}:</strong> {{ $store->opening_time ?? '-' }}</p>
                            <p class="card-text mb-1"><strong>{{ __('messages.store.closing_time') }}:</strong> {{ $store->closing_time ?? '-' }}</p>
                            <div class="d-flex justify-content-between mt-2">
                                <a href="{{ route('stores.edit', $store) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('stores.destroy', $store) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('{{ __('messages.store.confirm_delete') }}')">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
