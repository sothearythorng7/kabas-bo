@php
    $currentRoute = Route::currentRouteName();
@endphp

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'dashboard')) active @endif"
           href="{{ route('financial.dashboard', $store->id) }}">@t('dashboard')</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'general-invoices')) active @endif"
        href="{{ route('financial.general-invoices.index', $store->id) }}">@t('invoices')</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'shift')) active @endif"
           href="{{ route('financial.shifts.index', $store->id) }}">@t('Shifts')</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'transactions')) active @endif"
           href="{{ route('financial.transactions.index', $store->id) }}">@t('Transactions')</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'accounts')) active @endif"
           href="{{ route('financial.accounts.index', $store->id) }}">@t('Comptes')</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'payment-methods')) active @endif"
        href="{{ route('financial.payment-methods.index', $store->id) }}">@t('Méthodes de paiement')</a>
    </li>
</ul>
