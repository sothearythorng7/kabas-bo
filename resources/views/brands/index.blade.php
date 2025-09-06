@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.brand.title') }}</h1>
    
    <a href="{{ route('brands.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.brand.btnCreate') }}
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('messages.brand.name') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($brands as $brand)
                <tr>
                    <td>{{ $brand->id }}</td>
                    <td>{{ $brand->name }}</td>
                    <td class="d-flex justify-content-end gap-1">
                        <a href="{{ route('brands.edit', $brand) }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                        </a>
                        <form action="{{ route('brands.destroy', $brand) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" 
                                onclick="return confirm('{{ __('messages.brand.confirm_delete') }}')">
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
            @foreach($brands as $brand)
            <div class="col-12 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <h5 class="card-title mb-1">{{ $brand->name }}</h5>
                        <div class="d-flex justify-content-between mt-2">
                            <a href="{{ route('brands.edit', $brand) }}" class="btn btn-warning btn-sm">Edit</a>
                            <form action="{{ route('brands.destroy', $brand) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('{{ __('messages.brand.confirm_delete') }}')">Delete</button>
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
