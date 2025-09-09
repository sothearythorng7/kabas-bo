<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'Laravel') }}</title>
<link rel="dns-prefetch" href="//fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@vite(['resources/sass/app.scss', 'resources/js/app.js'])

@stack('styles')
</head>
<body>
@if(Auth::check())
<div id="app">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark border-right" id="sidebar-wrapper">
            <div class="sidebar-heading d-flex justify-content-between align-items-center">
                    <div class="d-none d-md-block mb-2">
                        <img src="{{ asset('images/kabas_logo.png') }}" alt="Logo" style="width: 60px; height: auto;">
                    </div>
                    <span>Kabas<br />Concept<br />Store</span>
                <button id="menu-close" class="btn btn-sm btn-outline-secondary d-md-none">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="list-group list-group-flush" id="mainmenu">
                <a href="{{ route('dashboard') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-house-door"></i> {{ __('messages.menu.dashboard') }}
                </a>
                <a href="{{ route('products.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-list-check"></i> {{ __('messages.menu.catalog') }}
                </a>
                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                   data-bs-toggle="collapse" href="#submenu-stock" role="button" aria-expanded="false" aria-controls="submenu-stock">
                    <div><i class="bi bi-inboxes"></i> {{ __('messages.menu.stocks') }}</div>
                    <span class="caret"></span>
                </a>
                <a href="{{ route('resellers.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-shop"></i> {{ __('messages.menu.resellers') }}
                </a>
                <div class="collapse" id="submenu-stock">
                    <a href="{{ route('stocks.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-eye"></i> {{ __('messages.menu.stock_overview') }}
                    </a>
                    <a href="{{ route('stock-movements.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-arrow-left-right"></i> {{ __('messages.menu.stock_movements') }}
                    </a>
                </div>
                <a href="{{ route('scanner') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-house-door"></i> Test Scanner
                </a>
                </a>
                    <a href="{{ route('suppliers.index') }}" class="list-group-item list-group-item-action">
                    <i class="bi bi-truck"></i> {{ __('messages.menu.suppliers') }}
                </a>
                @role('admin')


                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                   data-bs-toggle="collapse" href="#submenu-compta" role="button" aria-expanded="false" aria-controls="submenu-compta">
                    <div><i class="bi bi-coin"></i> {{ __('messages.menu.acconting') }}</div>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="submenu-compta">
                    <a href="{{ route('warehouse-invoices.billsoverview') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-receipt"></i> {{ __('messages.menu.bills_overview') }}
                    </a>
                    <a href="{{ route('reseller-invoices.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-receipt"></i> {{ __('messages.menu.reselers_invoices') }}
                    </a>
                    <a href="{{ route('stock-value') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-currency-dollar"></i> {{ __('messages.menu.stock_value') }}
                    </a>
                </div>


                <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                   data-bs-toggle="collapse" href="#submenu-parametres" role="button" aria-expanded="false" aria-controls="submenu-parametres">
                    <div><i class="bi bi-gear"></i> {{ __('messages.menu.settings') }}</div>
                    <span class="caret"></span>
                </a>
                <div class="collapse" id="submenu-parametres">
                    <a href="{{ route('roles.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-card-checklist"></i> {{ __('messages.menu.roles') }}
                    </a>
                    <a href="{{ route('stores.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-shop"></i> {{ __('messages.menu.shops') }}
                    </a>
                    <a href="{{ route('brands.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-ubuntu"></i> {{ __('messages.menu.brands') }}
                    </a>
                    <a href="{{ route('users.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-people"></i> {{ __('messages.menu.users') }}
                    </a>
                    <a href="{{ route('categories.index') }}" class="list-group-item list-group-item-action ps-4">
                        <i class="bi bi-card-checklist"></i> {{ __('messages.menu.categories') }}
                    </a>
                </div>
                <a class="list-group-item list-group-item-action" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    {{ __('messages.menu.logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
                @endrole
            </div>
        </div>

        <!-- Page content -->
        <div id="page-content-wrapper" class="flex-grow-1">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom d-md-none">
                <button class="btn btn-primary d-md-none" id="menu-open" style="margin-left:10px;">Menu</button>
            </nav>
            <div class="container-fluid">
                @include('partials.flash-messages')
                
                @yield('content')
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar-wrapper');
    const btnOpen = document.getElementById('menu-open');
    const btnClose = document.getElementById('menu-close');

    // MOBILE ONLY
    function isMobile() { return window.innerWidth < 768; }

    function openSidebar() { wrapper.classList.add('toggled'); }
    function closeSidebar() { wrapper.classList.remove('toggled'); }

    // bouton bleu pour ouvrir menu mobile
    if(btnOpen) btnOpen.addEventListener('click', openSidebar);
    // bouton X pour fermer menu mobile
    if(btnClose) btnClose.addEventListener('click', closeSidebar);

    // clic en dehors pour fermer menu mobile
    document.addEventListener('click', function(e) {
        if(!isMobile()) return;
        if(!wrapper.classList.contains('toggled')) return;

        if(!sidebar.contains(e.target) && !btnOpen.contains(e.target) && !btnClose.contains(e.target)) {
            closeSidebar();
        }
    });

    // réajustement au resize
    window.addEventListener('resize', function() {
        if(isMobile()) {
            wrapper.classList.remove('toggled'); // mobile fermé par défaut
        } else {
            wrapper.classList.add('toggled');    // desktop ouvert par défaut
        }
    });

    // initialisation
    if(isMobile()) wrapper.classList.remove('toggled');
    else wrapper.classList.add('toggled');
});
</script>

<style>
:root {
    --sidebar-max: 18rem;
}

.crud_title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    width:100%;
    background-color: #f8f9fa;
    padding: 10px;
    margin-bottom:20px;
    border-bottom:solid 1px blue;
}

#sidebar-wrapper {
    min-height: 100vh;
    width: var(--sidebar-max);
    background-color: #e9ecef !important;
    transition: transform .25s ease-out;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1030;
}
#page-content-wrapper {
    width: 100%;
    transition: margin .25s ease-out;
    margin-left: var(--sidebar-max);
}
.sidebar-heading { padding: .875rem 1.25rem; font-size: 1.2rem; background-color: #e9ecef; }
#submenu-parametres a { background-color: #f8f9fa; }
.caret { display:inline-block;width:0;height:0;margin-left:.5em;vertical-align:middle;border-top:.4em solid;border-right:.4em solid transparent;border-left:.4em solid transparent;transition:transform .2s ease;}
a[aria-expanded="true"] .caret { transform: rotate(180deg); }

/* Mobile */
@media (max-width: 767.98px) {
    #sidebar-wrapper { transform: translateX(-100%); }
    #wrapper.toggled #sidebar-wrapper { transform: translateX(0); }
    #page-content-wrapper { margin-left: 0 !important; }
    #menu-open { display: block; }
    #menu-close { display: inline-block; }
}

/* Desktop */
@media (min-width: 768px) {
    #sidebar-wrapper { transform: translateX(0); }
    #wrapper.toggled #sidebar-wrapper { transform: translateX(0); }
    #page-content-wrapper { margin-left: var(--sidebar-max); }
    #menu-close { display: none !important; }
}

.border-left-primary {
  border-left: 0.25rem solid #4e73df !important;
}

.border-bottom-primary {
  border-bottom: 0.25rem solid #4e73df !important;
}

.border-left-secondary {
  border-left: 0.25rem solid #858796 !important;
}

.border-bottom-secondary {
  border-bottom: 0.25rem solid #858796 !important;
}

.border-left-success {
  border-left: 0.25rem solid #1cc88a !important;
}

.border-bottom-success {
  border-bottom: 0.25rem solid #1cc88a !important;
}

.border-left-info {
  border-left: 0.25rem solid #36b9cc !important;
}

.border-bottom-info {
  border-bottom: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
  border-left: 0.25rem solid #f6c23e !important;
}

.border-bottom-warning {
  border-bottom: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
  border-left: 0.25rem solid #e74a3b !important;
}

.border-bottom-danger {
  border-bottom: 0.25rem solid #e74a3b !important;
}

.border-left-light {
  border-left: 0.25rem solid #f8f9fc !important;
}

.border-bottom-light {
  border-bottom: 0.25rem solid #f8f9fc !important;
}

.border-left-dark {
  border-left: 0.25rem solid #5a5c69 !important;
}

.border-bottom-dark {
  border-bottom: 0.25rem solid #5a5c69 !important;
}
</style>
@stack('styles')

@else
@yield('content')
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

@stack('scripts')
</body>
</html>
