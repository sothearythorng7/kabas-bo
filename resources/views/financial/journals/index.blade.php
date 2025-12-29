@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.financial.journals') }} – {{ $store->name }}</h1>
    @include('financial.layouts.nav')
    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.financial.date') }}</th>
                <th>{{ __('messages.financial.transaction') }}</th>
                <th>{{ __('messages.financial.user') }}</th>
                <th>{{ __('messages.financial.action') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($journals as $j)
            <tr>
                <td>{{ $j->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $j->transaction?->label ?? '—' }}</td>
                <td>{{ $j->user?->name }}</td>
                <td>{{ ucfirst($j->action) }}</td>
                <td class="text-end">
                    <a href="{{ route('financial.journals.show', [$store->id, $j->id]) }}" class="btn btn-sm btn-info">{{ __('messages.financial.view') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center">{{ __('messages.financial.no_journals') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    {{ $journals->links() }}
</div>
@endsection
