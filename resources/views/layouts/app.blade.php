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
        <div class="bg-grey border-right" id="sidebar-wrapper">
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
                @include('partials.sidebar-menu')
                <!-- Logout -->
                <a class="list-group-item list-group-item-action" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    {{ __('messages.menu.logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
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

    function isMobile() { return window.innerWidth < 768; }

    function openSidebar() { wrapper.classList.add('toggled'); }
    function closeSidebar() { wrapper.classList.remove('toggled'); }

    if(btnOpen) btnOpen.addEventListener('click', openSidebar);
    if(btnClose) btnClose.addEventListener('click', closeSidebar);

    document.addEventListener('click', function(e) {
        if(!isMobile()) return;
        if(!wrapper.classList.contains('toggled')) return;
        if(!sidebar.contains(e.target) && !btnOpen.contains(e.target) && !btnClose.contains(e.target)) {
            closeSidebar();
        }
    });

    window.addEventListener('resize', function() {
        if(isMobile()) wrapper.classList.remove('toggled');
        else wrapper.classList.add('toggled');
    });
    if(isMobile()) wrapper.classList.remove('toggled'); else wrapper.classList.add('toggled');

    // Sous-menus glissants
    document.querySelectorAll('.has-submenu').forEach(item => {
        item.addEventListener('click', () => {
            const targetId = item.dataset.target;
            const menu = document.getElementById(targetId);
            if(menu) menu.classList.add('active');
        });
    });

    document.querySelectorAll('.go-back').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.menu-level').classList.remove('active');
        });
    });
});
</script>

<style>
:root { --sidebar-max: 18rem; }
#sidebar-wrapper { min-height:100vh; width:var(--sidebar-max); background:#e9ecef; transition:transform .25s ease-out; position:fixed; top:0; left:0; z-index:1030; overflow-x:hidden; }
#page-content-wrapper { width:100%; transition:margin .25s ease-out; margin-left:var(--sidebar-max); }
.sidebar-heading { padding:.875rem 1.25rem; font-size:1.2rem; background:#e9ecef; }
.menu-level { display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:#f8f9fa; overflow-y:auto; transition:left .3s ease; z-index:1040; }
.menu-level.active { display:block; }
.go-back { cursor:pointer; font-weight:bold; }
.caret { display:inline-block;width:0;height:0;margin-left:.5em;vertical-align:middle;border-top:.4em solid;border-right:.4em solid transparent;border-left:.4em solid transparent;transition:transform .2s ease;}
a[aria-expanded="true"] .caret { transform: rotate(180deg); }
.breadcrumb {
    background: #395068ff;
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
    border-radius: 0.25rem;
}
.crud_title {
    font-size:25px;
    width:100%;
    border-bottom:solid 1px #0d6efd;
    margin-bottom:20px;
}
.dropdown-noarrow.dropdown-toggle::after {
  display: none !important;
  content: none !important;
}

.table-hover > tbody > tr:hover {
    background-color: #C0C0C0 !important;
    --bs-table-hover-bg: #C0C0C0 !important;
    cursor:pointer;
}

@media(max-width:767.98px){#sidebar-wrapper{transform:translateX(-100%);}#wrapper.toggled #sidebar-wrapper{transform:translateX(0);}#page-content-wrapper{margin-left:0!important;}#menu-open{display:block;}#menu-close{display:inline-block;} }
@media(min-width:768px){#sidebar-wrapper{transform:translateX(0);}#wrapper.toggled #sidebar-wrapper{transform:translateX(0);}#page-content-wrapper{margin-left:var(--sidebar-max);}#menu-close{display:none!important;} }
</style>
@else
@yield('content')
@endif

@stack('scripts')
</body>
</html>
