@php
    $currentRoute = Route::currentRouteName();
@endphp

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'dashboard')) active @endif"
           href="{{ route('financial.dashboard', $store->id) }}">{{ __('messages.financial.dashboard_title') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'general-invoices')) active @endif"
        href="{{ route('financial.general-invoices.index', $store->id) }}">{{ __('messages.financial.invoices') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'shift')) active @endif"
           href="{{ route('financial.shifts.index', $store->id) }}">{{ __('messages.financial.shifts') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'transactions')) active @endif"
           href="{{ route('financial.transactions.index', $store->id) }}">{{ __('messages.financial.transaction') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'accounts')) active @endif"
           href="{{ route('financial.accounts.index', $store->id) }}">{{ __('messages.financial.accounts_menu') }}</a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if(str_contains($currentRoute, 'payment-methods')) active @endif"
        href="{{ route('financial.payment-methods.index', $store->id) }}">{{ __('messages.financial.payment_methods_menu') }}</a>
    </li>
</ul>
