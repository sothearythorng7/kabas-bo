@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">{{ __('messages.payment_method.title') }}</h1>
    @include('financial.layouts.nav')
    <a href="{{ route('financial.payment-methods.create', $store->id) }}" class="btn btn-primary mb-3">
        {{ __('messages.payment_method.title_create') }}
    </a>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th>{{ __('messages.payment_method.name') }}</th>
                <th>{{ __('messages.payment_method.code') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($methods as $m)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionsDropdown{{ $m->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="actionsDropdown{{ $m->id }}">
                            <li>
                                <a class="dropdown-item" href="{{ route('financial.payment-methods.edit', [$store->id, $m->id]) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.payment_method.edit') }}
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('financial.payment-methods.destroy', [$store->id, $m->id]) }}">
                                    @csrf @method('DELETE')
                                    <button class="dropdown-item" onclick="return confirm('{{ __('messages.payment_method.delete_confirm') }}')">
                                        <i class="bi bi-trash-fill"></i> {{ __('messages.payment_method.delete') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $m->name }}</td>
                <td>{{ $m->code }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center">{{ __('messages.payment_method.no_methods') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $methods->links() }}
</div>
@endsection
