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
        <div class="container-fluid" style="padding-top: 0.5rem;">
        @php $breadcrumb = buildBreadcrumbFromHistory(5); @endphp
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb d-flex flex-wrap align-items-center gap-1 p-0 mb-2">
                @foreach($breadcrumb as $index => $item)
                    <li class="breadcrumb-item m-0 p-0 d-flex align-items-center">
                        <a href="{{ $item['url'] }}" class="badge bg-secondary text-decoration-none px-2 py-1">
                            {{ $item['label'] }}
                        </a>

                        {{-- Séparateur sauf pour le dernier élément --}}
                        @if(!$loop->last)
                            <i class="bi bi-caret-right-fill mx-1 text-muted"></i>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
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
<script>
function trackCurrentUrl() {
    const url = window.location.href;
    console.log(url);
    fetch("{{ route('track-url') }}", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ url })
    })
    .then(res => res.json());
}

// Track la page au chargement
trackCurrentUrl();

// Track lorsqu’un onglet est changé (Bootstrap tabs)
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => {
        setTimeout(() => {
            trackCurrentUrl(window.location.href);
        }, 50);
    });
});
</script>

</body>
</html>
