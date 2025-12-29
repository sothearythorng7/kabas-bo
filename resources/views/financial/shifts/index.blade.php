@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4 crud_title">{{ __('messages.financial.current_shift') }} – {{ $store->name }}</h1>

    @include('financial.layouts.nav')

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> {{ __('messages.financial.filters') }}</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('financial.shifts.index', $store->id) }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.financial.start_date') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.financial.end_date') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('messages.financial.user') }}</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- {{ __('messages.financial.all_users') }} --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> {{ __('messages.financial.filter_action') }}
                    </button>
                    <a href="{{ route('financial.shifts.index', $store->id) }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> {{ __('messages.financial.reset_action') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if($shifts->isNotEmpty())
        <!-- Liste des shifts filtrés -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list"></i> {{ __('messages.financial.shift_list') }}</h5>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('messages.financial.id') }}</th>
                            <th>{{ __('messages.financial.user') }}</th>
                            <th>{{ __('messages.financial.start') }}</th>
                            <th>{{ __('messages.financial.end') }}</th>
                            <th>{{ __('messages.financial.duration') }}</th>
                            <th>{{ __('messages.financial.opening_cash') }}</th>
                            <th>{{ __('messages.financial.closing_cash') }}</th>
                            <th>{{ __('messages.financial.cash_difference') }}</th>
                            <th>{{ __('messages.financial.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shifts as $s)
                        <tr>
                            <td>{{ $s->id }}</td>
                            <td>
                                {{ $s->user->name ?? 'N/A' }}
                                @if($s->shiftUsers && $s->shiftUsers->count() > 1)
                                    <span class="badge bg-info ms-1" title="{{ $s->shiftUsers->pluck('user.name')->join(', ') }}" data-bs-toggle="tooltip">
                                        <i class="bi bi-people"></i> {{ $s->shiftUsers->count() }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $s->started_at ? $s->started_at->format('d/m/Y H:i') : 'N/A' }}</td>
                            <td>{{ $s->ended_at ? $s->ended_at->format('d/m/Y H:i') : __('messages.financial.in_progress') }}</td>
                            <td>
                                @if($s->started_at && $s->ended_at)
                                    {{ $s->started_at->diffForHumans($s->ended_at, true) }}
                                @else
                                    {{ $s->started_at->diffForHumans(null, true) }}
                                @endif
                            </td>
                            <td>${{ number_format($s->opening_cash ?? 0, 2) }}</td>
                            <td>
                                @if($s->closing_cash !== null)
                                    ${{ number_format($s->closing_cash, 2) }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($s->cash_difference !== null)
                                    <span class="badge bg-{{ $s->cash_difference == 0 ? 'success' : ($s->cash_difference > 0 ? 'warning' : 'danger') }}">
                                        {{ $s->cash_difference > 0 ? '+' : '' }}${{ number_format($s->cash_difference, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('financial.shifts.index', array_merge(['store' => $store->id, 'shift_id' => $s->id], request()->only(['date_from', 'date_to', 'user_id']))) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> {{ __('messages.financial.view_details') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(!$shift)
        <div class="alert alert-info">{{ __('messages.financial.no_shift') }}</div>
    @else
        <!-- Informations du shift -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> {{ __('messages.financial.shift_details') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.user') }}:</strong><br>
                        {{ $shift->user->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.start') }}:</strong><br>
                        {{ $shift->started_at ? $shift->started_at->format('d/m/Y H:i') : 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.end') }}:</strong><br>
                        {{ $shift->ended_at ? $shift->ended_at->format('d/m/Y H:i') : __('messages.financial.in_progress') }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.duration') }}:</strong><br>
                        @if($shift->started_at && $shift->ended_at)
                            {{ $shift->started_at->diffForHumans($shift->ended_at, true) }}
                        @else
                            {{ $shift->started_at->diffForHumans(null, true) }}
                        @endif
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.opening_cash') }}:</strong><br>
                        ${{ number_format($shift->opening_cash ?? 0, 2) }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.closing_cash') }}:</strong><br>
                        @if($shift->closing_cash !== null)
                            ${{ number_format($shift->closing_cash, 2) }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.cash_difference') }}:</strong><br>
                        @if($shift->cash_difference !== null)
                            <span class="badge bg-{{ $shift->cash_difference == 0 ? 'success' : ($shift->cash_difference > 0 ? 'warning' : 'danger') }}">
                                {{ $shift->cash_difference > 0 ? '+' : '' }}${{ number_format($shift->cash_difference, 2) }}
                            </span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.financial.visitors') }}:</strong><br>
                        {{ $shift->visitors_count ?? 'N/A' }}
                    </div>
                </div>

                @if($shift->shiftUsers && $shift->shiftUsers->count() > 1)
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong><i class="bi bi-people"></i> {{ __('messages.Shift Users') }}:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($shift->shiftUsers as $shiftUser)
                            <li>
                                <strong>{{ $shiftUser->user->name ?? 'N/A' }}</strong>
                                <span class="text-muted">
                                    ({{ $shiftUser->started_at ? $shiftUser->started_at->format('H:i') : 'N/A' }}
                                    -
                                    {{ $shiftUser->ended_at ? $shiftUser->ended_at->format('H:i') : __('messages.financial.in_progress') }})
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.financial.sales_count') }}</h5>
                        <p class="card-text fs-3">{{ $shiftStats['number_of_sales'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.financial.total_sales_amount') }}</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_sales'], 2) }} $</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.financial.total_items_sold') }}</h5>
                        <p class="card-text fs-3">{{ $shiftStats['total_items'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('messages.financial.total_discounts') }}</h5>
                        <p class="card-text fs-3">{{ number_format($shiftStats['total_discounts'], 2) }} $</p>
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>{{ __('messages.financial.sale_id') }}</th>
                    <th>{{ __('messages.financial.items_count') }}</th>
                    <th>{{ __('messages.financial.total_collected') }}</th>
                    <th>{{ __('messages.financial.total_discounts') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($sales as $sale)
                @php
                    $totalDiscounts = 0;

                    foreach ($sale->items as $item) {
                        foreach ($item->discounts ?? [] as $d) {
                            if ($d['type'] === 'amount') {
                                // Check scope: 'unit' means per unit, otherwise per line
                                if (($d['scope'] ?? 'line') === 'unit') {
                                    $totalDiscounts += $d['value'] * $item->quantity;
                                } else {
                                    $totalDiscounts += $d['value'];
                                }
                            } elseif ($d['type'] === 'percent') {
                                $totalDiscounts += ($d['value'] / 100) * $item->price * $item->quantity;
                            }
                        }
                    }

                    foreach ($sale->discounts ?? [] as $d) {
                        if ($d['type'] === 'amount') {
                            $totalDiscounts += $d['value'];
                        } elseif ($d['type'] === 'percent') {
                            $totalDiscounts += ($d['value'] / 100) * $sale->total;
                        }
                    }

                @endphp
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#itemsModal{{ $sale->id }}">
                            {{ $sale->items->sum('quantity') }}
                        </a>
                    </td>
                    <td>{{ number_format($sale->total, 2) }} $</td>
                    <td>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#discountModal{{ $sale->id }}">
                            {{ number_format($totalDiscounts, 2) }} $
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <!-- Modales en dehors de la table -->
        @foreach($sales as $sale)
            <!-- Modal Items -->
            <div class="modal fade" id="itemsModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('messages.financial.sale_items') }} #{{ $sale->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.financial.name') }}</th>
                                        <th>{{ __('messages.financial.ean') }}</th>
                                        <th>{{ __('messages.financial.unit_price') }}</th>
                                        <th>{{ __('messages.financial.quantity') }}</th>
                                        <th>{{ __('messages.financial.discount') }}</th>
                                        <th>{{ __('messages.financial.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($sale->items as $item)
                                    @php
                                        $unitPrice = $item->price ?? 0;
                                        $quantity = $item->quantity ?? 1;
                                        $lineGross = $unitPrice * $quantity;

                                        // Calculate item discounts with proper scope handling
                                        $itemDiscounts = 0;
                                        foreach ($item->discounts ?? [] as $d) {
                                            if ($d['type'] === 'amount') {
                                                if (($d['scope'] ?? 'line') === 'unit') {
                                                    $itemDiscounts += $d['value'] * $quantity;
                                                } else {
                                                    $itemDiscounts += $d['value'];
                                                }
                                            } elseif ($d['type'] === 'percent') {
                                                $itemDiscounts += ($d['value'] / 100) * $lineGross;
                                            }
                                        }

                                        $lineTotal = $lineGross - $itemDiscounts;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($item->product)
                                                {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                                            @elseif($item->is_delivery)
                                                {{ __('messages.financial.delivery_service') }}
                                                @if($item->delivery_address)
                                                    <br><small class="text-muted">{{ $item->delivery_address }}</small>
                                                @endif
                                            @else
                                                {{ __('messages.financial.unknown_item') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->product && $item->product->ean)
                                                {{ $item->product->ean }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>${{ number_format($unitPrice, 2) }}</td>
                                        <td>{{ $quantity }}</td>
                                        <td>${{ number_format($itemDiscounts, 2) }}</td>
                                        <td><strong>${{ number_format($lineTotal, 2) }}</strong></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Discounts -->
            <div class="modal fade" id="discountModal{{ $sale->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('messages.financial.sale_discounts') }} #{{ $sale->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <ul>
                                @foreach($sale->items as $item)
                                    @foreach($item->discounts ?? [] as $d)
                                        @php
                                            // Calculate discount amount based on type and scope
                                            $discountAmount = 0;
                                            if ($d['type'] === 'amount') {
                                                if (($d['scope'] ?? 'line') === 'unit') {
                                                    $discountAmount = $d['value'] * $item->quantity;
                                                } else {
                                                    $discountAmount = $d['value'];
                                                }
                                            } elseif ($d['type'] === 'percent') {
                                                $discountAmount = ($d['value'] / 100) * $item->price * $item->quantity;
                                            }
                                        @endphp
                                        <li>
                                            @if($item->product)
                                                {{ $item->product->name[app()->getLocale()] ?? reset($item->product->name) }}
                                            @elseif($item->is_delivery)
                                                {{ __('messages.financial.delivery_service') }}
                                            @else
                                                {{ __('messages.financial.unknown_item') }}
                                            @endif
                                            : {{ number_format($discountAmount, 2) }} $
                                            @if($d['type'] === 'amount' && ($d['scope'] ?? 'line') === 'unit')
                                                <small class="text-muted">({{ $d['value'] }}$ × {{ $item->quantity }})</small>
                                            @elseif($d['type'] === 'percent')
                                                <small class="text-muted">({{ $d['value'] }}%)</small>
                                            @endif
                                        </li>
                                    @endforeach
                                @endforeach
                                @foreach($sale->discounts ?? [] as $d)
                                    @php
                                        $globalDiscount = $d['type'] === 'amount'
                                            ? $d['value']
                                            : ($d['value'] / 100) * $sale->total;
                                    @endphp
                                    <li>{{ __('messages.financial.global') }} : {{ number_format($globalDiscount, 2) }} $
                                        @if($d['type'] === 'percent')
                                            <small class="text-muted">({{ $d['value'] }}%)</small>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
