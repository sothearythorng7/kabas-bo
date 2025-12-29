@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.voucher.title') }}</h1>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.voucher.stats.total_issued') }}</h5>
                    <p class="card-text h3">{{ $stats['total_issued'] }} <small class="fs-6">({{ number_format($stats['total_issued_value'], 2) }} $)</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.voucher.stats.active') }}</h5>
                    <p class="card-text h3">{{ $stats['active'] }} <small class="fs-6">({{ number_format($stats['active_value'], 2) }} $)</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.voucher.stats.used') }}</h5>
                    <p class="card-text h3">{{ $stats['used'] }} <small class="fs-6">({{ number_format($stats['used_value'], 2) }} $)</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.voucher.stats.expired') }}</h5>
                    <p class="card-text h3">{{ $stats['expired'] }} <small class="fs-6">({{ number_format($stats['expired_value'], 2) }} $)</small></p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('vouchers.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.voucher.btnCreate') }}
        </a>
        <a href="{{ route('vouchers.export', request()->query()) }}" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> {{ __('messages.btn.export') }}
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('vouchers.index') }}" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.voucher.code') }}</label>
                    <input type="text" name="code" class="form-control" value="{{ request('code') }}" placeholder="KBA...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.voucher.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('messages.all') }}</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('messages.voucher.statuses.active') }}</option>
                        <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>{{ __('messages.voucher.statuses.used') }}</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('messages.voucher.statuses.expired') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('messages.voucher.statuses.cancelled') }}</option>
                    </select>
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
                    <label class="form-label">{{ __('messages.date_from') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('messages.date_to') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">{{ __('messages.btn.filter') }}</button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary">{{ __('messages.btn.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('messages.voucher.code') }}</th>
                    <th class="text-end">{{ __('messages.voucher.amount') }}</th>
                    <th>{{ __('messages.voucher.status') }}</th>
                    <th>{{ __('messages.voucher.source') }}</th>
                    <th>{{ __('messages.store.name') }}</th>
                    <th>{{ __('messages.voucher.created_at') }}</th>
                    <th>{{ __('messages.voucher.expires_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $voucher)
                    <tr>
                        <td style="width: 1%; white-space: nowrap;">
                            <div class="dropdown">
                                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('vouchers.show', $voucher) }}">
                                            <i class="bi bi-eye"></i> {{ __('messages.btn.view') }}
                                        </a>
                                    </li>
                                    @if($voucher->status === 'active')
                                    <li>
                                        <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $voucher->id }}">
                                            <i class="bi bi-x-circle"></i> {{ __('messages.voucher.cancel') }}
                                        </button>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                        <td><code>{{ $voucher->code }}</code></td>
                        <td class="text-end">{{ number_format($voucher->amount, 2) }} $</td>
                        <td>
                            @switch($voucher->status)
                                @case('active')
                                    <span class="badge bg-success">{{ __('messages.voucher.statuses.active') }}</span>
                                    @break
                                @case('used')
                                    <span class="badge bg-secondary">{{ __('messages.voucher.statuses.used') }}</span>
                                    @break
                                @case('expired')
                                    <span class="badge bg-warning text-dark">{{ __('messages.voucher.statuses.expired') }}</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">{{ __('messages.voucher.statuses.cancelled') }}</span>
                                    @break
                            @endswitch
                        </td>
                        <td>
                            @switch($voucher->source_type)
                                @case('exchange')
                                    <span class="badge bg-info">{{ __('messages.voucher.sources.exchange') }}</span>
                                    @break
                                @case('manual')
                                    <span class="badge bg-primary">{{ __('messages.voucher.sources.manual') }}</span>
                                    @break
                                @case('promotion')
                                    <span class="badge bg-purple">{{ __('messages.voucher.sources.promotion') }}</span>
                                    @break
                            @endswitch
                        </td>
                        <td>{{ $voucher->createdAtStore?->name ?? '-' }}</td>
                        <td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $voucher->expires_at->format('d/m/Y') }}</td>
                    </tr>

                    @if($voucher->status === 'active')
                    <div class="modal fade" id="cancelModal{{ $voucher->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('vouchers.cancel', $voucher) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ __('messages.voucher.cancel') }} {{ $voucher->code }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('messages.voucher.cancellation_reason') }}</label>
                                            <textarea name="reason" class="form-control" required rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.btn.close') }}</button>
                                        <button type="submit" class="btn btn-danger">{{ __('messages.voucher.cancel') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">{{ __('messages.no_data') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $vouchers->withQueryString()->links() }}
</div>
@endsection
