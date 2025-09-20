<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name', 'Laravel') }}</title>

{{-- Vite CSS & JS --}}
@vite(['resources/sass/app.scss', 'resources/js/app.js'])

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

@stack('styles')
</head>
<body>

@if(Auth::check())
<div id="app">
    {{-- Navbar --}}
    <nav class="navbar navbar-light bg-light border-bottom sticky-top" style="z-index: 1050; padding: 0.4rem 1rem;">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <span class="navbar-brand mb-0 h1" style="font-size: 1rem;">Kabas Concept Store</span>
            <button class="btn btn-primary btn-lg" id="menu-btn">
                <i class="bi bi-list"></i> Menu
            </button>
        </div>
    </nav>

    {{-- Flashs + contenu --}}
    <div class="container-fluid mt-3" style="padding-top: 0.5rem;">
        @include('partials.flash-messages')
        @yield('content')
    </div>
</div>

{{-- Modal Menu --}}
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-2">
                <div id="menuContainer" data-menu='@json($menuForJs)'></div>
            </div>
        </div>
    </div>
</div>

{{-- Initialisation JS --}}
@php
    $flashData = session()->only(['success', 'error', 'warning', 'info']);
    $errorData = $errors->any() ? $errors->all() : [];
@endphp

<script type="module">
document.addEventListener('DOMContentLoaded', function() {
    const menuContainer = document.getElementById('menuContainer');
    const menuData = JSON.parse(menuContainer.dataset.menu);
    initMenu(menuData);

    // Flash messages
    initFlash(@json($flashData), @json($errorData));
});
</script>

@else
    @yield('content')
@endif

@stack('scripts')
</body>
</html>
