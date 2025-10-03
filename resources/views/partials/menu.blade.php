@php
if (! function_exists('renderMenuItems')) {
    function renderMenuItems(array $items, $isSub = false) {
        foreach ($items as $it) {
            // URL
            $url = '#';
            if (isset($it['route'])) {
                try {
                    if (is_array($it['route'])) {
                        $routeName = $it['route'][0];
                        $routeParams = $it['route'][1] ?? [];
                        foreach ($routeParams as $k => $v) {
                            if ($v instanceof \Illuminate\Database\Eloquent\Model) {
                                $routeParams[$k] = $v->getKey();
                            }
                        }
                        $url = route($routeName, $routeParams);
                    } else {
                        if (preg_match('/^(http|https):\\/\\//', $it['route']) || str_starts_with($it['route'], '/')) {
                            $url = $it['route'];
                        } else {
                            $url = route($it['route']);
                        }
                    }
                } catch (\Throwable $e) {
                    $url = '#';
                }
            }

            // Sous-menu
            $submenu = [];
            if (!empty($it['submenu'])) {
                $submenu = $it['submenu'];
            } elseif (!empty($it['dynamic_submenu'])) {
                try {
                    $dyn = is_callable($it['dynamic_submenu']) ? call_user_func($it['dynamic_submenu']) : $it['dynamic_submenu'];
                    if (is_array($dyn)) $submenu = $dyn;
                } catch (\Throwable $e) {
                    $submenu = [];
                }
            }

            // Render
            if (!empty($submenu)) {
                if ($isSub) {
                    echo '<li class="dropdown-submenu">';
                    echo '<a class="dropdown-item dropdown-toggle" href="#" role="button" aria-expanded="false">';
                    echo e(__($it['label']));
                    echo '</a>';
                    echo '<ul class="dropdown-menu">';
                    renderMenuItems($submenu, true);
                    echo '</ul>';
                    echo '</li>';
                } else {
                    echo '<li class="nav-item dropdown">';
                    echo '<a class="nav-link dropdown-toggle mx-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">';
                    echo e(__($it['label']));
                    echo '</a>';
                    echo '<ul class="dropdown-menu">';
                    renderMenuItems($submenu, true);
                    echo '</ul>';
                    echo '</li>';
                }
            } else {
                if ($isSub) {
                    echo '<li><a class="dropdown-item" href="'.e($url).'">'.e(__($it['label'])).'</a></li>';
                } else {
                    echo '<li class="nav-item"><a class="nav-link mx-2" href="'.e($url).'">'.e(__($it['label'])).'</a></li>';
                }
            }
        }
    }
}
@endphp

<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    @php renderMenuItems(config('menu', [])) @endphp
</ul>
