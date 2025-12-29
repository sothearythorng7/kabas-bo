@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>{{ __('messages.resellers.return_details') }} #{{ $return->id }}</h1>
        <a href="{{ route('resellers.show', $reseller->id) }}?tab=returns" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('messages.btn.back') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Informations générales -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('messages.resellers.return_info') }}</h5>
            @php
                $statusClass = match($return->status) {
                    'draft' => 'warning',
                    'validated' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary',
                };
            @endphp
            <span class="badge bg-{{ $statusClass }} fs-6">{{ ucfirst($return->status) }}</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>{{ __('messages.resellers.reseller') }}:</strong>
                    <p>{{ $reseller->name }}</p>
                </div>
                <div class="col-md-3">
                    <strong>{{ __('messages.resellers.destination_store') }}:</strong>
                    <p>{{ $return->destinationStore->name }} ({{ ucfirst($return->destinationStore->type) }})</p>
                </div>
                <div class="col-md-3">
                    <strong>{{ __('messages.resellers.created_by') }}:</strong>
                    <p>{{ $return->user->name ?? '-' }}</p>
                </div>
                <div class="col-md-3">
                    <strong>{{ __('messages.resellers.created_at') }}:</strong>
                    <p>{{ $return->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
            @if($return->note)
                <div class="row">
                    <div class="col-12">
                        <strong>{{ __('messages.resellers.note') }}:</strong>
                        <p class="mb-0">{{ $return->note }}</p>
                    </div>
                </div>
            @endif
            @if($return->validated_at)
                <div class="row mt-2">
                    <div class="col-12">
                        <strong>{{ __('messages.resellers.validated_at') }}:</strong>
                        <span class="text-success">{{ $return->validated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Produits du retour -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('messages.resellers.returned_products') }} ({{ $return->items->count() }})</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.stock_value.ean') }}</th>
                        <th>{{ __('messages.product.name') }}</th>
                        <th>{{ __('messages.product.brand') }}</th>
                        <th class="text-center">{{ __('messages.resellers.quantity') }}</th>
                        <th>{{ __('messages.resellers.return_reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $item)
                        <tr>
                            <td>{{ $item->product->ean ?? '-' }}</td>
                            <td>{{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}</td>
                            <td>{{ $item->product->brand->name ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $item->quantity }}</span>
                            </td>
                            <td>{{ $item->reason ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="text-end"><strong>{{ __('messages.resellers.total') }}:</strong></td>
                        <td class="text-center"><strong class="badge bg-success fs-6">{{ $return->total_items }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Actions -->
    @if($return->status === 'draft')
        <div class="card">
            <div class="card-body d-flex justify-content-end gap-2">
                <form action="{{ route('resellers.returns.cancel', [$reseller->id, $return->id]) }}" method="POST"
                      onsubmit="return confirm('{{ __('messages.resellers.confirm_cancel_return') }}')">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}
                    </button>
                </form>
                <form action="{{ route('resellers.returns.validate', [$reseller->id, $return->id]) }}" method="POST"
                      onsubmit="return confirm('{{ __('messages.resellers.confirm_validate_return') }}')">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> {{ __('messages.resellers.validate_return') }}
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
