@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.user_edit.title') }}</h1>

    <a href="{{ route('users.create') }}" class="btn btn-success mb-3"><i class="bi bi-plus-circle-fill"></i> {{ __('messages.user_edit.btnCreate') }}</a>

    {{-- Table pour écrans md et plus --}}
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('messages.user_edit.name') }}</th>
                    <th>{{ __('messages.user_edit.email') }}</th>
                    <th>{{ __('messages.user_edit.role') }}</th>
                    <th>{{ __('messages.user_edit.language') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                        <td>{{ $user->locale }}</td>
                        <td class="d-flex justify-content-end gap-1">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}</a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.user_edit.confirm_delete') }}')"><i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}</button>
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
            @foreach($users as $user)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <h5 class="card-title mb-1">{{ $user->name }}</h5>
                            <p class="card-text mb-1"><strong>{{ __('messages.user_edit.email') }}:</strong> {{ $user->email }}</p>
                            <p class="card-text mb-1"><strong>{{ __('messages.user_edit.role') }}:</strong> {{ $user->roles->pluck('name')->implode(', ') }}</p>
                            <p class="card-text mb-2"><strong>{{ __('messages.user_edit.language') }}:</strong> {{ $user->locale }}</p>
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}</a>
                                <form action="{{ route('users.destroy', $user) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.user_edit.confirm_delete') }}')"><i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{ $users->links() }}
</div>
@endsection
