@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.roles.title') }}</h1>

    <a href="{{ route('roles.create') }}" class="btn btn-success mb-3"><i class="bi bi-plus-circle-fill"></i> {{ __('messages.roles.btnCreate') }}</a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Table pour écrans md et + --}}
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('messages.roles.name') }}</th>
                    <th>{{ __('messages.roles.permissions') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->permissions->pluck('name')->implode(', ') }}</td>
                        <td class="d-flex justify-content-end gap-1">
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}</a>
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.roles.confirm_delete') }}')"><i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}</button>
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
            @foreach($roles as $role)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-1">{{ $role->name }}</h5>
                            <p class="card-text mb-2"><strong>{{ __('messages.roles.permissions') }}:</strong> {{ $role->permissions->pluck('name')->implode(', ') }}</p>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}</a>
                                <form action="{{ route('roles.destroy', $role) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.roles.confirm_delete') }}')"><i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{ $roles->links() }}
</div>
@endsection
