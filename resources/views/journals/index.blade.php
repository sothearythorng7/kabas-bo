@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.journals.title') }} - {{ $site->name }}</h1>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.dashboard.index', $site) }}">{{ __('messages.store_nav.general_info') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('stores.journals.index', $site) }}">{{ __('messages.store_nav.journals') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.payments.index', $site) }}">{{ __('messages.store_nav.supplier_payments') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expenses.index', $site) }}">{{ __('messages.store_nav.expenses') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('stores.expense-categories.index', $site) }}">{{ __('messages.store_nav.categories') }}</a>
        </li>
    </ul>
    <a href="{{ route('stores.journals.create', $site) }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.journals.add_transaction') }}
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.common.date') }}</th>
                <th>{{ __('messages.journals.account') }}</th>
                <th>{{ __('messages.journals.type') }}</th>
                <th>{{ __('messages.journals.amount') }}</th>
                <th>{{ __('messages.common.description') }}</th>
                <th class="text-end">{{ __('messages.Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($journals as $journal)
            <tr>
                <td>{{ $journal->date->format('d/m/Y') }}</td>
                <td>{{ $journal->account->name }}</td>
                <td>{{ ucfirst($journal->type) }}</td>
                <td>{{ number_format($journal->amount, 2, ',', ' ') }} $</td>
                <td>{{ $journal->description }}</td>
                <td class="text-end">
                    <a href="{{ route('stores.journals.show', [$site, $journal]) }}" class="btn btn-info btn-sm">
                        <i class="bi bi-eye-fill"></i> {{ __('messages.journals.view') }}
                    </a>
                    <form action="{{ route('stores.journals.destroy', [$site, $journal]) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.journals.confirm_delete') }}')">
                            <i class="bi bi-trash-fill"></i> {{ __('messages.Supprimer') }}
                        </button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($journals instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $journals->links() }}
    @endif
</div>
@endsection
