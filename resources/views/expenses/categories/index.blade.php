@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.expense_categories.title') }}</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.dashboard.index', $site) }}">{{ __('messages.store_nav.general_info') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.journals.index', $site) }}">{{ __('messages.store_nav.journals') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.payments.index', $site) }}">{{ __('messages.store_nav.supplier_payments') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">{{ __('messages.store_nav.expenses') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.expense-categories.index', $site) }}">{{ __('messages.store_nav.categories') }}</a>
        </li>
    </ul>
    <a href="{{ route('stores.expense-categories.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.expense_categories.add') }}
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.common.name') }}</th>
                <th>{{ __('messages.common.description') }}</th>
                <th class="text-end">{{ __('messages.Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
                <td class="text-end">
                    <a href="{{ route('stores.expense-categories.edit', [$site, $category]) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill"></i> {{ __('messages.Modifier') }}
                    </a>
                    <form action="{{ route('stores.expense-categories.destroy', [$site, $category]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.expense_categories.confirm_delete') }}')">
                            <i class="bi bi-trash-fill"></i> {{ __('messages.Supprimer') }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
