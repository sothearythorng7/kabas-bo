@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.exchange.title') }}</h1>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.exchange.stats.total') }}</h5>
                    <p class="card-text h3">{{ $stats['total_exchanges'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.exchange.stats.return_value') }}</h5>
                    <p class="card-text h3">{{ number_format($stats['total_return_value'], 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.exchange.stats.new_items_value') }}</h5>
                    <p class="card-text h3">{{ number_format($stats['total_new_items_value'], 2) }} $</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.exchange.stats.vouchers_generated') }}</h5>
                    <p class="card-text h3">{{ $stats['exchanges_with_voucher'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('exchanges.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.exchange.original_sale') }}</label>
                    <input type="number" name="original_sale_id" class="form-control" value="{{ request('original_sale_id') }}" placeholder="#">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.store.name') }}</label>
                    <select name="store_id" class="form-select">
                        <option value="">{{ __('messages.all') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.user.name') }}</label>
                    <select name="user_id" class="form-select">
                        <option value="">{{ __('messages.all') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.date_from') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.date_to') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">{{ __('messages.btn.filter') }}</button>
                    <a href="{{ route('exchanges.index') }}" class="btn btn-outline-secondary">{{ __('messages.btn.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>{{ __('messages.exchange.original_sale') }}</th>
                    <th class="text-end">{{ __('messages.exchange.return_total') }}</th>
                    <th class="text-end">{{ __('messages.exchange.new_items_total') }}</th>
                    <th class="text-end">{{ __('messages.exchange.balance') }}</th>
                    <th>{{ __('messages.exchange.voucher') }}</th>
                    <th>{{ __('messages.store.name') }}</th>
                    <th>{{ __('messages.user.name') }}</th>
                    <th>{{ __('messages.date') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exchanges as $exchange)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;">
                            <a href="{{ route('exchanges.show', $exchange) }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                        <td>{{ $exchange->id }}</td>
                        <td>
                            <a href="#">#{{ $exchange->original_sale_id }}</a>
                        </td>
                        <td class="text-end text-danger">{{ number_format($exchange->return_total, 2) }} $</td>
                        <td class="text-end text-success">{{ number_format($exchange->new_items_total, 2) }} $</td>
                        <td class="text-end">
                            @if($exchange->balance > 0)
                                <span class="text-success">+{{ number_format($exchange->balance, 2) }} $</span>
                            @elseif($exchange->balance < 0)
                                <span class="text-danger">{{ number_format($exchange->balance, 2) }} $</span>
                            @else
                                <span class="text-muted">0.00 $</span>
                            @endif
                        </td>
                        <td>
                            @if($exchange->generatedVoucher)
                                <a href="{{ route('vouchers.show', $exchange->generatedVoucher) }}">
                                    <code>{{ $exchange->generatedVoucher->code }}</code>
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $exchange->store?->name ?? '-' }}</td>
                        <td>{{ $exchange->user?->name ?? '-' }}</td>
                        <td>{{ $exchange->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">{{ __('messages.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $exchanges->withQueryString()->links() }}
</div>
@endsection
