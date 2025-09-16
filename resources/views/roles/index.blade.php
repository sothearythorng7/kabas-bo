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
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th></th> {{-- Dropdown --}}
                        <th class="text-center">ID</th>
                        <th>{{ __('messages.roles.name') }}</th>
                        <th>{{ __('messages.roles.permissions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            {{-- Dropdown bouton --}}
                            <td style="width: 1%; white-space: nowrap;" class="text-start">
                                <div class="dropdown">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownRole{{ $role->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownRole{{ $role->id }}">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('roles.edit', $role) }}">
                                                <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('{{ __('messages.roles.confirm_delete') }}')">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit">
                                                    <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>

                            <td class="text-center">{{ $role->id }}</td>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->permissions->pluck('name')->implode(', ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $roles->links() }}
        </div>
    </div>
    @endsection
