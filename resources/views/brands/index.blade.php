@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.brand.title') }}</h1>
    
    <a href="{{ route('brands.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.brand.btnCreate') }}
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th> {{-- Dropdown actions --}}
                    <th class="text-center">ID</th>
                    <th>{{ __('messages.brand.name') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($brands as $brand)
                <tr>
                    <td style="width: 1%; white-space: nowrap;" class="text-start">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownBrand{{ $brand->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownBrand{{ $brand->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('brands.edit', $brand) }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" onsubmit="return confirm('{{ __('messages.brand.confirm_delete') }}')">
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
                    <td class="text-center">{{ $brand->id }}</td>
                    <td>{{ $brand->name }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $brands->links() }}
</div>
@endsection
