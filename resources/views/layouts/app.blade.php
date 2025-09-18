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
@php
    function prepareMenuForJs(array $items) {
        $out = [];
        foreach ($items as $it) {
            $entry = [];
            $entry['label'] = isset($it['label']) ? __($it['label']) : '';
            $entry['icon']  = $it['icon'] ?? '';
            $entry['method'] = $it['method'] ?? null;
            $entry['attributes'] = $it['attributes'] ?? [];

            $url = null;
            if (isset($it['route'])) {
                try {
                    if (is_array($it['route'])) {
                        $routeName = $it['route'][0];
                        $routeParams = $it['route'][1] ?? [];
                        foreach ($routeParams as $k => $v) {
                            if ($v instanceof \Illuminate\Database\Eloquent\Model) $routeParams[$k] = $v->getKey();
                        }
                        $url = route($routeName, $routeParams);
                    } else {
                        if (preg_match('/^(http|https):\\/\\//', $it['route']) || str_starts_with($it['route'], '/')) {
                            $url = $it['route'];
                        } else {
                            $url = route($it['route']);
                        }
                    }
                } catch (\Exception $e) {
                    $url = null;
                }
            }
            $entry['url'] = $url;

            $sub = [];
            if (isset($it['submenu'])) {
                $sub = prepareMenuForJs($it['submenu']);
            } elseif (isset($it['dynamic_submenu']) && $it['dynamic_submenu']) {
                try {
                    $dyn = is_callable($it['dynamic_submenu']) ? call_user_func($it['dynamic_submenu']) : $it['dynamic_submenu'];
                    if (is_array($dyn)) $sub = prepareMenuForJs($dyn);
                } catch (\Exception $e) {
                    $sub = [];
                }
            }
            $entry['submenu'] = $sub;

            $out[] = $entry;
        }
        return $out;
    }

    $menuForJs = prepareMenuForJs(config('menu') ?? []);
@endphp

<div id="app">
    <nav class="navbar navbar-light bg-light border-bottom sticky-top" style="z-index: 1050; padding: 0.4rem 1rem;">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <span class="navbar-brand mb-0 h1" style="font-size: 1rem;">Kabas Concept Store</span>
            <button class="btn btn-primary btn-lg" id="menu-btn">
                <i class="bi bi-list"></i> Menu
            </button>
        </div>
    </nav>

    <div class="container-fluid mt-3" style="padding-top: 0.5rem;">
        @include('partials.flash-messages')
        @yield('content')
    </div>
</div>

<!-- Modal Menu -->
<div class="modal fade" id="menuModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body p-2">
        <div id="menuContainer" class="position-relative w-100 h-100"></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const menuData = @json($menuForJs);
    const csrfToken = '{{ csrf_token() }}';

    const menuBtn = document.getElementById('menu-btn');
    const modalEl = document.getElementById('menuModal');
    const menuContainer = document.getElementById('menuContainer');
    const bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });

    const panels = [];

    function createPanel(items, title) {
        const panel = document.createElement('div');
        panel.className = 'menu-panel position-absolute top-0 start-0 w-100 h-100 d-flex flex-wrap justify-content-start align-content-start';
        panel.style.transform = 'translateX(100%)';
        panel.style.transition = 'transform .28s ease';

        // bouton back comme icône
        if (panels.length > 0) {
            const backCard = document.createElement('div');
            backCard.className = 'menu-card d-flex flex-column align-items-center justify-content-center text-center go-back';
            backCard.innerHTML = '<i class="bi bi-arrow-left"></i><div>Retour</div>';
            backCard.addEventListener('click', goBack);
            panel.appendChild(backCard);
        }

        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'menu-card d-flex flex-column align-items-center justify-content-center text-center';
            card.innerHTML = `<i class="${item.icon || ''}"></i><div>${item.label || ''}</div>`;

            card.addEventListener('click', () => {
                if (item.submenu && item.submenu.length > 0) {
                    pushPanel(item.submenu, item.label);
                    return;
                }
                const method = (item.method || 'GET').toUpperCase();
                if (method !== 'GET') {
                    if (!item.url) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = item.url;
                    form.style.display = 'none';
                    const t = document.createElement('input');
                    t.type = 'hidden'; t.name = '_token'; t.value = csrfToken;
                    form.appendChild(t);
                    if (method !== 'POST') {
                        const m = document.createElement('input');
                        m.type = 'hidden'; m.name = '_method'; m.value = method;
                        form.appendChild(m);
                    }
                    document.body.appendChild(form);
                    form.submit();
                    return;
                }
                if (item.url) window.location.href = item.url;
            });

            panel.appendChild(card);
        });

        return panel;
    }

    function pushPanel(items, title) {
        const newPanel = createPanel(items, title);
        menuContainer.appendChild(newPanel);
        const previous = panels.length ? panels[panels.length - 1] : null;
        requestAnimationFrame(() => {
            if (previous) previous.style.transform = 'translateX(-100%)';
            newPanel.style.transform = 'translateX(0)';
        });
        panels.push(newPanel);
    }

    function goBack() {
        if (panels.length < 2) return;
        const current = panels.pop();
        const previous = panels[panels.length - 1];
        current.style.transform = 'translateX(100%)';
        previous.style.transform = 'translateX(0)';
        current.addEventListener('transitionend', function handler() {
            current.removeEventListener('transitionend', handler);
            current.remove();
        });
    }

    menuBtn.addEventListener('click', () => {
        menuContainer.innerHTML = '';
        panels.length = 0;
        const first = createPanel(menuData, 'Menu');
        menuContainer.appendChild(first);
        requestAnimationFrame(() => first.style.transform = 'translateX(0)');
        panels.push(first);
        bsModal.show();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        menuContainer.innerHTML = '';
        panels.length = 0;
    });
});
</script>

<style>
/* Modal dimensions - desktop par défaut */
.modal-dialog { max-width: 70vw; }
.modal-content { height: 50vh; overflow: hidden; }

/* Tablette (sm/md) */
@media (min-width: 576px) and (max-width: 991.98px) {
  .modal-dialog { max-width: 80vw; }
  .modal-content { height: 60vh; }
}

/* Mobile (xs) */
@media (max-width: 575.98px) {
  .modal-dialog { max-width: 95vw; }
  .modal-content { height: 80vh; }
}

/* Panels sliding */
.menu-panel { left:0; top:0; }

/* Grille flex wrap */
.menu-panel {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  align-content: flex-start;
  gap: 1rem;
  padding: 1rem;
  width: 100%;
  height: 100%;
  overflow: hidden;
  box-sizing: border-box;
}

/* Cartes fixes */
/* Cartes fixes */
.menu-card {
  width: 150px;
  height: 150px;
  border-radius: .6rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-weight: 500;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;      /* Bordure fine grise */
  box-shadow: 0 2px 6px rgba(0,0,0,0.08); /* Ombre légère */
}
.menu-card i {
  font-size: 2rem;
  margin-bottom: .3rem;
  color: #0d6efd;
}
.menu-card:hover {
  background-color: #e9f2ff;
  color: #0d6efd;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12); /* Ombre plus marquée au hover */
}
.menu-card:hover i {
  color: #0d6efd;
}

/* Bouton retour */
.menu-card.go-back {
  background-color: #f8f9fa;
  font-style: italic;
  color: #6c757d;
}
.menu-card.go-back:hover {
  background-color: #e9ecef;
  color: #0d6efd;
}

</style>

@else
@yield('content')
@endif

@stack('scripts')
</body>
</html>
