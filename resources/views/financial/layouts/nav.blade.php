@php
    $currentRoute = Route::currentRouteName();
@endphp

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'dashboard')) active @endif"
           href="{{ route('financial.dashboard', $store->id) }}">Dashboard</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'transactions')) active @endif"
           href="{{ route('financial.transactions.index', $store->id) }}">Transactions</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'journals')) active @endif"
           href="{{ route('financial.journals.index', $store->id) }}">Journaux</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'accounts')) active @endif"
           href="{{ route('financial.accounts.index', $store->id) }}">Comptes</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'payment-methods')) active @endif"
        href="{{ route('financial.payment-methods.index', $store->id) }}">Méthodes de paiement</a>
    </li>
</ul>
