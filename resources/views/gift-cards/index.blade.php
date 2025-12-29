@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.gift_cards.title') }}</h1>

    <a href="{{ route('gift-cards.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.gift_cards.create') }}
    </a>

    <div class="mb-3">
        <form action="{{ route('gift-cards.index') }}" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="{{ __('messages.gift_cards.search_placeholder') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> {{ __('messages.search') }}
                </button>
            </div>
            @if(request('q'))
            <div class="col-md-2">
                <a href="{{ route('gift-cards.index') }}" class="btn btn-secondary w-100">
                    <i class="bi bi-x-circle"></i> {{ __('messages.reset') }}
                </a>
            </div>
            @endif
        </form>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th></th>
                <th></th>
                <th>{{ __('messages.common.name') }}</th>
                <th>{{ __('messages.form.amount') }}</th>
                <th>{{ __('messages.form.active') }}</th>
                <th class="text-center" style="width:120px;">{{ __('messages.gift_cards.codes_generated') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($giftCards as $giftCard)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('gift-cards.edit', $giftCard) }}">
                                    <i class="bi bi-pencil-square"></i> {{ __('messages.Modifier') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('gift-cards.destroy', $giftCard) }}" method="POST" onsubmit="return confirm('{{ __('messages.gift_cards.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash"></i> {{ __('messages.Supprimer') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
                <td>{{ $giftCard->id }}</td>
                <td>
                    <a href="{{ route('gift-cards.edit', $giftCard) }}" class="link-primary text-decoration-none">
                        {{ $giftCard->name['fr'] ?? $giftCard->name['en'] ?? 'N/A' }}
                    </a>
                </td>
                <td>{{ number_format($giftCard->amount, 2) }} $</td>
                <td>
                    <span class="badge {{ $giftCard->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $giftCard->is_active ? __('messages.yes') : __('messages.no') }}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge bg-info">{{ $giftCard->codes()->count() }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $giftCards->links() }}
    </div>
</div>

@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast show" role="alert">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">{{ __('messages.flash.success') }}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    </div>
@endif
@endsection
