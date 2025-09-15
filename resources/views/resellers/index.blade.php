@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.resellers.title') }}</h1>

    <a href="{{ route('resellers.create') }}" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> {{ __('messages.btn.add') }}
    </a>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>{{ __('messages.resellers.name') }}</th>
                <th>{{ __('messages.resellers.type') }}</th>
                <th>{{ __('messages.resellers.contacts') }}</th>
                <th>{{ __('messages.main.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($resellers as $reseller)
            <tr>
                {{-- Nom --}}
                <td>{{ $reseller->name }}</td>

                {{-- Type --}}
                <td>{{ ucfirst($reseller->type ?? 'N/A') }}</td>

                {{-- Contacts --}}
                <td>
                    @if(property_exists($reseller, 'is_shop') && $reseller->is_shop)
                        <div class="text-muted">-</div>
                    @else
                        @foreach($reseller->contacts as $c)
                            <div>{{ $c->name }} ({{ $c->email ?? '-' }})</div>
                        @endforeach
                    @endif
                </td>

                {{-- Actions --}}
                <td>
                    @php
                        // Détecte si c'est un shop pour générer l'ID correct pour le lien "view"
                        $showId = property_exists($reseller, 'is_shop') && $reseller->is_shop
                            ? $reseller->id      // ex: "shop-123"
                            : $reseller->id;     // ID du Reseller
                    @endphp

                    <a href="{{ route('resellers.show', $showId) }}" class="btn btn-sm btn-info">
                        {{ __('messages.btn.view') }}
                    </a>

                    @if(!property_exists($reseller, 'is_shop'))
                        <a href="{{ route('resellers.edit', $reseller) }}" class="btn btn-sm btn-warning">
                            {{ __('messages.btn.edit') }}
                        </a>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-muted">@t("No resellers found.")</td>
            </tr>
        @endforelse
        </tbody>

    </table>

    {{ $resellers->links() }}
</div>
@endsection
