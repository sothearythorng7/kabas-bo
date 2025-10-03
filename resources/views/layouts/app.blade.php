<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name', 'Laravel') }}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stack('styles')
</head>
<body>
@if(Auth::check())
<div id="app">
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Kabas Concept Store</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                @include('partials.menu')

                <ul class="navbar-nav ms-auto">
                    <!-- Compte utilisateur -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button class="dropdown-item" type="submit">
                                        <i class="bi bi-box-arrow-right me-1"></i> {{ __('Logout') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        @include('partials.flash-messages')
        @yield('content')
    </div>
</div>
@else
@yield('content')
@endif

@stack('scripts')

<script>
document.addEventListener("DOMContentLoaded", function() {
    // ouvrir les sous-sous-menus
    document.querySelectorAll('.dropdown-submenu > .dropdown-toggle').forEach(function(toggle){
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();

            const submenu = toggle.parentElement.querySelector('ul.dropdown-menu');
            if (!submenu) return;

            // fermer les autres sous-sous-menus du même parent
            const parentMenu = toggle.closest('.dropdown-menu');
            if (parentMenu) {
                parentMenu.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function(sm){
                    if (sm !== submenu) sm.classList.remove('show');
                });
            }

            submenu.classList.toggle('show');
        });
    });

    // fermer tous les sous-sous-menus quand dropdown principal se ferme
    document.querySelectorAll('.dropdown').forEach(function(dd){
        dd.addEventListener('hidden.bs.dropdown', function(){
            this.querySelectorAll('.dropdown-submenu .dropdown-menu.show').forEach(function(sm){
                sm.classList.remove('show');
            });
        });
    });
});


</script>
</body>
</html>
