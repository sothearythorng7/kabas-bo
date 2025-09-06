@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h1 class="crud_title">{{ __('messages.supplier.title') }}</h1>
        
        <a href="{{ route('suppliers.create') }}" class="btn btn-success mb-3"><i class="bi bi-plus-circle-fill"></i> {{ __('messages.supplier.btnCreate') }}</a>

        <div class="d-none d-md-block">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('messages.supplier.name') }}</th>
                        <th>{{ __('messages.supplier.address') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->id }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->address }}</td>
                            <td class="d-flex justify-content-end gap-1">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}</a>
                                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.supplier.confirm_delete') }}')"><i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Cartes pour mobile --}}
        <div class="d-md-none">
            <div class="row">
                @foreach($suppliers as $supplier)
                    <div class="col-12 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <h5 class="card-title mb-1">{{ $supplier->name }}</h5>
                                <p class="card-text mb-1"><strong>{{ __('messages.supplier.address') }}:</strong> {{ $supplier->address }}</p>
                                <div class="d-flex justify-content-between mt-2">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </a>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.supplier.confirm_delete') }}')">
                                            <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                        </button>
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
