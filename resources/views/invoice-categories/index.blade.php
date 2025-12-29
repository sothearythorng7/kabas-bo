@extends('layouts.app')

@section('content')
<div class="container mt-4">
    @if(request('store_id'))
        <div class="mb-3">
            <a href="{{ route('financial.general-invoices.index', request('store_id')) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> {{ __('messages.invoice_category.back_to_financial') }}
            </a>
        </div>
    @endif

    <h1 class="crud_title">{{ __('messages.invoice_category.title') }}</h1>

    <div class="mb-3">
        <a href="{{ route('invoice-categories.create', request()->only('store_id')) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> {{ __('messages.invoice_category.new_category') }}
        </a>
    </div>

    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th></th>
                <th>{{ __('messages.invoice_category.name') }}</th>
                <th>{{ __('messages.invoice_category.color') }}</th>
                <th>{{ __('messages.invoice_category.invoices_count') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($categories as $category)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('invoice-categories.edit', array_merge(['invoice_category' => $category->id], request()->only('store_id'))) }}">
                                    <i class="bi bi-pencil-fill"></i> {{ __('messages.invoice_category.edit') }}
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('invoice-categories.destroy', $category->id) }}">
                                    @csrf @method('DELETE')
                                    @if(request('store_id'))
                                        <input type="hidden" name="store_id" value="{{ request('store_id') }}">
                                    @endif
                                    <button class="dropdown-item" onclick="return confirm('{{ __('messages.invoice_category.confirm_delete') }}')">
                                        <i class="bi bi-trash-fill"></i> {{ __('messages.invoice_category.delete') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $category->name }}</td>
                <td>
                    <span class="badge" style="background-color: {{ $category->color }}">
                        {{ $category->color }}
                    </span>
                </td>
                <td>{{ $category->general_invoices_count }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">{{ __('messages.invoice_category.no_category') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $categories->links() }}
</div>
@endsection
