@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.user_edit.title') }}</h1>

    <a href="{{ route('users.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.user_edit.btnCreate') }}
    </a>

    <div class="d-none d-md-block">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th> {{-- Dropdown actions --}}
                    <th class="text-center">ID</th>
                    <th>{{ __('messages.user_edit.name') }}</th>
                    <th>{{ __('messages.user_edit.email') }}</th>
                    <th>{{ __('messages.user_edit.role') }}</th>
                    <th>{{ __('messages.user_edit.language') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td style="width: 1%; white-space: nowrap;" class="text-start">
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownUser{{ $user->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownUser{{ $user->id }}">
                                <li>
                                    <a class="dropdown-item" href="{{ route('users.edit', $user) }}">
                                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                    </a>
                                </li>
                                <li>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('messages.user_edit.confirm_delete') }}')">
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
                    <td class="text-center">{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                    <td>{{ $user->locale }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}
</div>
@endsection
