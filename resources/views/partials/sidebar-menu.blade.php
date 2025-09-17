@php
function renderMenu($items, $level = 0) {
    foreach ($items as $item) {
        $hasSub = isset($item['submenu']) || isset($item['dynamic_submenu']);
        $submenuId = 'submenu-' . md5($item['label'] . $level);

        $activeClass = '';
        $isActive = false;

        if (isset($item['active_pattern'])) {
            foreach (explode('|', $item['active_pattern']) as $pattern) {
                if (request()->is($pattern)) {
                    $isActive = true;
                    break;
                }
            }
        }

        if (!$isActive && isset($item['route'])) {
            if (is_array($item['route'])) {
                $routeName = $item['route'][0];
                $routeParams = $item['route'][1] ?? [];

                if (request()->routeIs($routeName) || request()->routeIs($routeName . '.*')) {
                    $match = true;
                    foreach ($routeParams as $key => $value) {
                        $current = request()->route($key);
                        if ($value instanceof \Illuminate\Database\Eloquent\Model) $value = $value->getKey();
                        if ($current instanceof \Illuminate\Database\Eloquent\Model) $current = $current->getKey();
                        if ((string)$current !== (string)$value) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) $isActive = true;
                }
            } else {
                if (request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*')) {
                    $isActive = true;
                }
            }
        }

        // Initialisation de la classe active
        $activeClass = $isActive ? 'active' : '';

        if ($hasSub) {
            $subItems = $item['submenu'] ?? ($item['dynamic_submenu'] ? call_user_func($item['dynamic_submenu']) : []);

            // Bufferiser les enfants
            ob_start();
            renderMenu($subItems, $level + 1);
            $subMenuHtml = ob_get_clean();

            // Détection d’enfant actif
            $hasActiveChild = strpos($subMenuHtml, 'class="list-group-item list-group-item-action active"') !== false;

            // Déterminer état actif et ouvert
            $activeClass = ($isActive || $hasActiveChild) ? 'active' : '';
            $openSubMenu = $hasActiveChild; // seulement un enfant actif → ouvrir automatiquement

            // Lien parent
            echo '<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center has-submenu ' . $activeClass . '" data-target="' . $submenuId . '">';
            echo '<div><i class="bi ' . $item['icon'] . '"></i> ' . __($item['label']) . '</div>';
            echo '<span class="caret"></span></a>';

            // Sous-menu toujours généré dans le DOM
            echo '<div class="list-group list-group-flush menu-level ' . ($openSubMenu ? 'show' : '') . '" id="' . $submenuId . '">';
            echo '<div class="sidebar-heading d-flex justify-content-between align-items-center">';
            echo '<div class="d-none d-md-block mb-2">';
            echo '<img src="' . asset("images/kabas_logo.png") . '" alt="Logo" style="width: 60px; height: auto;">';
            echo '</div><span>Kabas<br />Concept<br />Store</span></div>';

            // Bouton "back" dans le sous-menu
            echo '<a href="javascript:void(0)" class="list-group-item list-group-item-action go-back">';
            echo '<i class="bi bi-arrow-left"></i> ' . __('messages.menu.back') . '</a>';

            echo $subMenuHtml;
            echo '</div>';
        } else {
            // Lien simple
            try {
                $href = '#';
                if (isset($item['route'])) {
                    if (is_array($item['route'])) {
                        $routeName = $item['route'][0];
                        $routeParams = $item['route'][1] ?? [];
                        foreach ($routeParams as $key => $value) {
                            if ($value instanceof \Illuminate\Database\Eloquent\Model) $routeParams[$key] = $value->getKey();
                        }
                        $href = route($routeName, $routeParams);
                    } else {
                        $href = route($item['route']);
                    }
                }
            } catch (\Exception $e) {
                $href = '#';
            }

            echo '<a href="' . $href . '" class="list-group-item list-group-item-action ' . $activeClass . '">';
            echo '<i class="bi ' . $item['icon'] . '"></i> ' . __($item['label']);
            echo '</a>';
        }
    }
}
@endphp

@php
renderMenu(config('menu'));
@endphp
