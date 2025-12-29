@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">{{ __('messages.financial_account.title') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <a href="{{ route('financial.accounts.create', $store->id) }}" class="btn btn-primary mb-3">
        <i class="bi bi-plus-circle"></i> {{ __('messages.financial_account.title_create') }}
    </a>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th>{{ __('messages.financial_account.code') }}</th>
                <th>{{ __('messages.financial_account.name') }}</th>
                <th>{{ __('messages.financial_account.type') }}</th>
                <th>{{ __('messages.financial_account.parent') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($accounts as $acc)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionsDropdown{{ $acc->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $acc->id }}">
                            <li>
                                <a class="dropdown-item" href="{{ route('financial.accounts.edit', [$store->id, $acc->id]) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.financial_account.edit') }}
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('financial.accounts.destroy', [$store->id, $acc->id]) }}">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('{{ __('messages.financial_account.delete_confirm') }}')">
                                        <i class="bi bi-trash-fill"></i> {{ __('messages.financial_account.delete') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $acc->code }}</td>
                <td>{{ $acc->name }}</td>
                <td>{{ $acc->type?->label() ?? '-' }}</td>
                <td>{{ $acc->parent?->name ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center">{{ __('messages.financial_account.no_accounts') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $accounts->links() }}
</div>
@endsection
