@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.stock_loss.title') }}</h1>
        <a href="{{ route('stock-losses.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.stock_loss.create_loss') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-losses.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.stock_loss.store') }}</label>
                    <select name="store_id" class="form-select">
                        <option value="">{{ __('messages.stock_loss.all_stores') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.stock_loss.type') }}</label>
                    <select name="type" class="form-select">
                        <option value="">{{ __('messages.stock_loss.all_types') }}</option>
                        <option value="pure_loss" {{ request('type') === 'pure_loss' ? 'selected' : '' }}>{{ __('messages.stock_loss.type_pure_loss') }}</option>
                        <option value="supplier_refund" {{ request('type') === 'supplier_refund' ? 'selected' : '' }}>{{ __('messages.stock_loss.type_supplier_refund') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('messages.stock_loss.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('messages.stock_loss.all_statuses') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('messages.stock_loss.status_draft') }}</option>
                        <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>{{ __('messages.stock_loss.status_validated') }}</option>
                        <option value="refund_requested" {{ request('status') === 'refund_requested' ? 'selected' : '' }}>{{ __('messages.stock_loss.status_refund_requested') }}</option>
                        <option value="refund_received" {{ request('status') === 'refund_received' ? 'selected' : '' }}>{{ __('messages.stock_loss.status_refund_received') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> {{ __('messages.stock_loss.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($losses->isEmpty())
                <p class="text-muted p-4">{{ __('messages.stock_loss.no_losses') }}</p>
            @else
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.stock_loss.reference') }}</th>
                            <th>{{ __('messages.common.date') }}</th>
                            <th>{{ __('messages.stock_loss.store') }}</th>
                            <th>{{ __('messages.stock_loss.type') }}</th>
                            <th>{{ __('messages.stock_loss.status') }}</th>
                            <th class="text-end">{{ __('messages.stock_loss.total_value') }}</th>
                            <th>{{ __('messages.stock_loss.created_by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($losses as $loss)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('stock-losses.show', $loss) }}'">
                                <td><strong>{{ $loss->reference }}</strong></td>
                                <td>{{ $loss->created_at->format('d/m/Y') }}</td>
                                <td>{{ $loss->store->name }}</td>
                                <td>
                                    @if($loss->isPureLoss())
                                        <span class="badge bg-danger">{{ __('messages.stock_loss.type_pure_loss') }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ __('messages.stock_loss.type_supplier_refund') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @switch($loss->status)
                                        @case('draft')
                                            <span class="badge bg-secondary">{{ __('messages.stock_loss.status_draft') }}</span>
                                            @break
                                        @case('validated')
                                            <span class="badge bg-success">{{ __('messages.stock_loss.status_validated') }}</span>
                                            @break
                                        @case('refund_requested')
                                            <span class="badge bg-info">{{ __('messages.stock_loss.status_refund_requested') }}</span>
                                            @break
                                        @case('refund_received')
                                            <span class="badge bg-primary">{{ __('messages.stock_loss.status_refund_received') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="text-end">${{ number_format($loss->total_value, 2) }}</td>
                                <td>{{ $loss->createdBy?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="mt-3">
        {{ $losses->links() }}
    </div>
</div>
@endsection
