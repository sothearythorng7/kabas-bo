@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.expenses.title') }} - {{ $site->name }}</h1>
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
            <a class="nav-link active" href="{{ route('stores.expenses.index', $site) }}">{{ __('messages.store_nav.expenses') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">{{ __('messages.store_nav.categories') }}</a>
        </li>
    </ul>
    <a href="{{ route('stores.expenses.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.expenses.add') }}
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.expenses.category') }}</th>
                <th>{{ __('messages.expenses.name') }}</th>
                <th>{{ __('messages.expenses.description') }}</th>
                <th>{{ __('messages.expenses.amount') }}</th>
                <th>{{ __('messages.expenses.document') }}</th>
                <th class="text-end">{{ __('messages.expenses.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->category->name }}</td>
                <td>{{ $expense->name }}</td>
                <td>{{ $expense->description }}</td>
                <td>{{ number_format($expense->amount, 2, ',', ' ') }} $</td>
                <td>
                    @if($expense->document)
                        <a href="{{ Storage::url($expense->document) }}" target="_blank">{{ __('messages.expenses.view') }}</a>
                    @else
                        -
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('stores.expenses.edit', [$site, $expense]) }}" class="btn btn-warning btn-sm">
                        <i class="bi bi-pencil-fill"></i> {{ __('messages.btn.edit') }}
                    </a>
                    <form action="{{ route('stores.expenses.destroy', [$site, $expense]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.expenses.confirm_delete') }}')">
                            <i class="bi bi-trash-fill"></i> {{ __('messages.btn.delete') }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
