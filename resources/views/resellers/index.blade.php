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
                    <th>{{ __('messages.resellers.active') }}</th>
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
                @php $isShop = property_exists($reseller, 'is_shop'); @endphp
                <tr class="reseller-row @if(!$isShop && !$reseller->is_active) reseller-inactive @endif" @if(!$isShop) data-reseller-row="{{ $reseller->id }}" @endif>
                    {{-- Nom --}}
                    <td>
                        {{ $reseller->name }}
                    </td>

                    {{-- Status --}}
                    <td>
                        @if($isShop)
                            <span class="badge bg-light text-muted">—</span>
                        @else
                            <select class="form-select form-select-sm reseller-toggle-active {{ $reseller->is_active ? 'bg-success-subtle' : 'bg-secondary-subtle' }}"
                                    data-reseller-id="{{ $reseller->id }}" style="width:100px;padding:2px 4px;font-size:0.8rem;">
                                <option value="1" {{ $reseller->is_active ? 'selected' : '' }}>{{ __('messages.resellers.active') }}</option>
                                <option value="0" {{ !$reseller->is_active ? 'selected' : '' }}>{{ __('messages.resellers.inactive') }}</option>
                            </select>
                        @endif
                    </td>

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
                    <td colspan="{{ 3 + count($deliveryStatuses) }}" class="text-muted">{{ __('messages.resellers.no_resellers') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $resellers->links() }}
</div>

<style>
    tr.reseller-inactive > td {
        background: repeating-linear-gradient(
            -45deg,
            #fef2f2 0,
            #fef2f2 10px,
            #fde4e4 10px,
            #fde4e4 20px
        ) !important;
        color: #9b6b6b;
    }
    tr.reseller-inactive > td:first-child {
        text-decoration: line-through;
        color: #9b6b6b;
    }
    /* Keep interactive elements at full contrast so they remain usable */
    tr.reseller-inactive > td .reseller-toggle-active,
    tr.reseller-inactive > td .btn,
    tr.reseller-inactive > td a {
        color: inherit;
        text-decoration: none;
    }
    tr.reseller-inactive > td .btn-info,
    tr.reseller-inactive > td .btn-warning {
        color: #fff;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    document.querySelectorAll('.reseller-toggle-active').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var el = this;
            var value = parseInt(el.value);
            var url = '{{ url("resellers") }}/' + el.dataset.resellerId + '/toggle-active';
            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ is_active: value }),
            }).then(function (r) {
                if (!r.ok) throw new Error('bad status');
                el.className = el.className.replace(/bg-\w+-subtle/g, '');
                el.classList.add(value ? 'bg-success-subtle' : 'bg-secondary-subtle');
                var row = document.querySelector('tr[data-reseller-row="' + el.dataset.resellerId + '"]');
                if (row) row.classList.toggle('reseller-inactive', !value);
            }).catch(function () {
                alert('Error updating active flag');
                location.reload();
            });
        });
    });
});
</script>
@endsection
