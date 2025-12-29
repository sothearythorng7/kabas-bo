@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.gift_boxes.title') }}</h1>

    <a href="{{ route('gift-boxes.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{ __('messages.gift_boxes.create') }}
    </a>

    <div class="mb-3">
        <form action="{{ route('gift-boxes.index') }}" method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="{{ __('messages.stock_value.search_placeholder') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> {{ __('messages.search') }}
                </button>
            </div>
            @if(request('q') || request('brand_id'))
            <div class="col-md-2">
                <a href="{{ route('gift-boxes.index') }}" class="btn btn-secondary w-100">
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
                <th>EAN</th>
                <th>{{ __('messages.common.name') }}</th>
                <th style="min-width:220px;">
                    <form action="{{ route('gift-boxes.index') }}" method="GET" id="brandFilterForm">
                        @if(request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif

                        <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.all_brands') }}</option>
                            <option value="none" {{ request('brand_id') === 'none' ? 'selected' : '' }}>
                                {{ __('messages.no_brand') }}
                            </option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}" {{ (string)$b->id === request('brand_id') ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </th>
                <th>{{ __('messages.product.price') }}</th>
                <th>{{ __('messages.gift_boxes.b2b_price') }}</th>
                <th>{{ __('messages.form.active') }}</th>
                <th>{{ __('messages.Best') }}</th>
                <th class="text-center" style="width:90px;">{{ __('messages.photo') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($giftBoxes as $giftBox)
            <tr>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle dropdown-noarrow" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('gift-boxes.edit', $giftBox) }}">
                                    <i class="bi bi-pencil-square"></i> {{ __('messages.Modifier') }}
                                </a>
                            </li>
                            <li>
                                <form action="{{ route('gift-boxes.destroy', $giftBox) }}" method="POST" onsubmit="return confirm('{{ __('messages.product.confirm_delete') }}');">
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
                <td>{{ $giftBox->id }}</td>
                <td>{{ $giftBox->ean }}</td>
                <td>
                    <a href="{{ route('gift-boxes.edit', $giftBox) }}" class="link-primary text-decoration-none">
                        {{ $giftBox->name['fr'] ?? $giftBox->name['en'] ?? 'N/A' }}
                    </a>
                </td>
                <td>{{ $giftBox->brand?->name ?? '-' }}</td>
                <td>{{ number_format($giftBox->price, 2) }} $</td>
                <td>{{ $giftBox->price_btob ? number_format($giftBox->price_btob, 2) . ' $' : '-' }}</td>
                <td>
                    <span class="badge {{ $giftBox->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $giftBox->is_active ? __('messages.yes') : __('messages.no') }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $giftBox->is_best_seller ? 'bg-warning' : 'bg-secondary' }}">
                        {{ $giftBox->is_best_seller ? __('messages.yes') : __('messages.no') }}
                    </span>
                </td>
                <td class="text-center">
                    @if($giftBox->images_count > 0)
                        <span class="badge bg-info">{{ $giftBox->images_count }}</span>
                    @else
                        <span class="badge bg-secondary">0</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center">
        {{ $giftBoxes->links() }}
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
