@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="crud_title">{{ __('messages.popup_event.title') }}</h1>
        <a href="{{ route('popup-events.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle-fill"></i> {{ __('messages.popup_event.create_event') }}
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
            <form method="GET" action="{{ route('popup-events.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.popup_event.store') }}</label>
                    <select name="store_id" class="form-select">
                        <option value="">{{ __('messages.popup_event.all_stores') }}</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('messages.popup_event.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('messages.popup_event.all_statuses') }}</option>
                        <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>{{ __('messages.popup_event.status_planned') }}</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('messages.popup_event.status_active') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('messages.popup_event.status_completed') }}</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('messages.popup_event.status_cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> {{ __('messages.popup_event.filter') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($events->isEmpty())
                <p class="text-muted p-4">{{ __('messages.popup_event.no_events') }}</p>
            @else
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('messages.popup_event.reference') }}</th>
                            <th>{{ __('messages.popup_event.name') }}</th>
                            <th>{{ __('messages.popup_event.location') }}</th>
                            <th>{{ __('messages.popup_event.store') }}</th>
                            <th>{{ __('messages.popup_event.dates') }}</th>
                            <th>{{ __('messages.popup_event.status') }}</th>
                            <th>{{ __('messages.popup_event.created_by') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('popup-events.show', $event) }}'">
                                <td><strong>{{ $event->reference }}</strong></td>
                                <td>{{ $event->name }}</td>
                                <td>{{ $event->location ?? '-' }}</td>
                                <td>{{ $event->store->name }}</td>
                                <td>
                                    {{ $event->start_date->format('d/m/Y') }}
                                    @if($event->end_date)
                                        - {{ $event->end_date->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td>
                                    @switch($event->status)
                                        @case('planned')
                                            <span class="badge bg-secondary">{{ __('messages.popup_event.status_planned') }}</span>
                                            @break
                                        @case('active')
                                            <span class="badge bg-success">{{ __('messages.popup_event.status_active') }}</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-primary">{{ __('messages.popup_event.status_completed') }}</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">{{ __('messages.popup_event.status_cancelled') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $event->createdBy?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="mt-3">
        {{ $events->links() }}
    </div>
</div>
@endsection
