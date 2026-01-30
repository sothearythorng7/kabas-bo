@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title') }}</h1>

    <a href="{{ route('resellers.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> {{ __('messages.btn.add') }}
    </a>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>{{ __('messages.resellers.name') }}</th>
                    <th>{{ __('messages.resellers.type') }}</th>
                    @foreach($deliveryStatuses as $statusKey => $statusLabel)
                        @if($statusKey === 'draft')
                            @continue
                        @endif
                        <th class="text-center" style="width: 100px;">
                            @if($statusKey === 'ready_to_ship')
                                <i class="bi bi-box-seam text-warning" title="{{ $statusLabel }}"></i>
                            @elseif($statusKey === 'shipped')
                                <i class="bi bi-truck text-success" title="{{ $statusLabel }}"></i>
                            @elseif($statusKey === 'cancelled')
                                <i class="bi bi-x-circle text-danger" title="{{ $statusLabel }}"></i>
                            @endif
                            <span class="d-none d-lg-inline ms-1">{{ __('messages.resellers.status_' . $statusKey) }}</span>
                        </th>
                    @endforeach
                    <th>{{ __('messages.main.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($resellers as $reseller)
                <tr>
                    {{-- Nom --}}
                    <td>{{ $reseller->name }}</td>

                    {{-- Type --}}
                    <td>
                        @if($reseller->type === 'consignment')
                            <span class="badge bg-info">{{ __('messages.resellers.consignment') }}</span>
                        @else
                            <span class="badge bg-primary">{{ __('messages.resellers.buyer') }}</span>
                        @endif
                    </td>

                    {{-- Delivery status counts --}}
                    @foreach($deliveryStatuses as $statusKey => $statusLabel)
                        @if($statusKey === 'draft')
                            @continue
                        @endif
                        <td class="text-center">
                            @php $count = $reseller->delivery_counts[$statusKey] ?? 0; @endphp
                            @if($count > 0)
                                @if($statusKey === 'ready_to_ship')
                                    <span class="badge bg-warning text-dark">{{ $count }}</span>
                                @elseif($statusKey === 'shipped')
                                    <span class="badge bg-success">{{ $count }}</span>
                                @elseif($statusKey === 'cancelled')
                                    <span class="badge bg-danger">{{ $count }}</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    @endforeach

                    {{-- Actions --}}
                    <td>
                        @php
                            $showId = property_exists($reseller, 'is_shop') && $reseller->is_shop
                                ? $reseller->id
                                : $reseller->id;
                        @endphp

                        <a href="{{ route('resellers.show', $showId) }}" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i>
                        </a>

                        @if(!property_exists($reseller, 'is_shop'))
                            <a href="{{ route('resellers.edit', $reseller) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + count($deliveryStatuses) }}" class="text-muted">{{ __('messages.resellers.no_resellers') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $resellers->links() }}
</div>
@endsection
