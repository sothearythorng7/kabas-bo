@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{__('messages.stock_movement.title')}}</h1>
    <a href="{{ route('stock-movements.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle-fill"></i> {{__('messages.stock_movement.create_title')}}
    </a>

    {{-- Desktop --}}
    <div class="d-none d-md-block">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{{__('messages.stock_movement.date')}}</th>
                    <th>{{__('messages.stock_movement.user')}}</th>
                    <th>{{__('messages.stock_movement.source')}}</th>
                    <th>{{__('messages.stock_movement.destination')}}</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                <tr>
                    <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->user->name }}</td>
                    <td>{{ $m->fromStore?->name ?? '-' }}</td>
                    <td>{{ $m->toStore?->name ?? '-' }}</td>
                    <td>
                        @switch($m->status)
                            @case(\App\Models\StockMovement::STATUS_DRAFT)
                                <span class="badge bg-secondary">{{ __('messages.stock_movement.status.draft')}}</span>
                                @break
                            @case(\App\Models\StockMovement::STATUS_VALIDATED)
                                <span class="badge bg-primary">{{ __('messages.stock_movement.status.validated')}}</span>
                                @break
                            @case(\App\Models\StockMovement::STATUS_IN_TRANSIT)
                                <span class="badge bg-warning text-dark">{{ __('messages.stock_movement.status.in_transit')}}</span>
                                @break
                            @case(\App\Models\StockMovement::STATUS_RECEIVED)
                                <span class="badge bg-success">{{ __('messages.stock_movement.status.received')}}</span>
                                @break
                            @case(\App\Models\StockMovement::STATUS_CANCELLED)
                                <span class="badge bg-danger">{{ __('messages.stock_movement.status.cancelled')}}</span>
                                @break
                        @endswitch
                    </td>
                    <td>
                        <a href="{{ route('stock-movements.show', $m) }}" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i> {{ __('messages.btn.show') }}
                        </a>

                        @if(in_array($m->status, [\App\Models\StockMovement::STATUS_VALIDATED, \App\Models\StockMovement::STATUS_IN_TRANSIT]))
                            <form action="{{ route('stock-movements.receive', $m) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-success">{{ __('messages.btn.receive')}}</button>
                            </form>

                            <form action="{{ route('stock-movements.cancel', $m) }}" method="POST" style="display:inline;" 
                                  onsubmit="return confirm('{{ __('messages.stock_movement.confirm_message')}}');">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-danger">{{ __('messages.btn.cancel')}}</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $movements->links() }}
    </div>

    {{-- Mobile --}}
    <div class="d-md-none">
        @foreach($movements as $m)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">
                    {{ $m->created_at->format('d/m/Y H:i') }} - {{ $m->user->name }}
                </h5>
                <p class="mb-1"><strong>Source:</strong> {{ $m->fromStore?->name ?? '-' }}</p>
                <p class="mb-1"><strong>Destination:</strong> {{ $m->toStore?->name ?? '-' }}</p>
                <p class="mb-1">
                    <strong>Status:</strong>
                    @switch($m->status)
                        @case(\App\Models\StockMovement::STATUS_DRAFT)
                            <span class="badge bg-secondary">{{ __('messages.stock_movement.status.draft')}}</span>
                            @break
                        @case(\App\Models\StockMovement::STATUS_VALIDATED)
                            <span class="badge bg-primary">{{ __('messages.stock_movement.status.validated')}}</span>
                            @break
                        @case(\App\Models\StockMovement::STATUS_IN_TRANSIT)
                            <span class="badge bg-warning text-dark">{{ __('messages.stock_movement.status.in_transit')}}</span>
                            @break
                        @case(\App\Models\StockMovement::STATUS_RECEIVED)
                            <span class="badge bg-success">{{ __('messages.stock_movement.status.received')}}</span>
                            @break
                        @case(\App\Models\StockMovement::STATUS_CANCELLED)
                            <span class="badge bg-danger">{{ __('messages.stock_movement.status.cancelled')}}</span>
                            @break
                    @endswitch
                </p>

                <a href="{{ route('stock-movements.show', $m) }}" class="btn btn-sm btn-info">
                    {{ __('messages.btn.show') }}
                </a>

                @if(in_array($m->status, [\App\Models\StockMovement::STATUS_VALIDATED, \App\Models\StockMovement::STATUS_IN_TRANSIT]))
                    <form action="{{ route('stock-movements.receive', $m) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-sm btn-success">{{ __('messages.btn.receive')}}</button>
                    </form>

                    <form action="{{ route('stock-movements.cancel', $m) }}" method="POST" class="d-inline" 
                          onsubmit="return confirm('{{ __('messages.stock_movement.confirm_message')}}');">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-sm btn-danger">{{ __('messages.btn.cancel')}}</button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
        {{ $movements->links() }}
    </div>
</div>
@endsection
