<?php

use Illuminate\Http\Request;

if (! function_exists('getRouteNameFromUrl')) {
    function getRouteNameFromUrl(string $url): ?string {
        try {
            $request = Request::create($url, 'GET');
            $route   = app('router')->getRoutes()->match($request);
            return $route->getName();
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (! function_exists('findLabelInMenu')) {
    function findLabelInMenu(string $routeName, array $menuConfig): ?string {
        foreach ($menuConfig as $item) {
            // Sous-menu dynamique
            if (isset($item['dynamic_submenu']) && is_callable($item['dynamic_submenu'])) {
                $submenu = call_user_func($item['dynamic_submenu']);
                if ($label = findLabelInMenu($routeName, $submenu)) {
                    return $label;
                }
            }

            // Sous-menu classique
            if (isset($item['submenu'])) {
                if ($label = findLabelInMenu($routeName, $item['submenu'])) {
                    return $label;
                }
            }

            // Cas simple
            if (isset($item['route'])) {
                $routeConfig     = $item['route'];
                $routeConfigName = is_array($routeConfig) ? $routeConfig[0] : $routeConfig;
                if ($routeConfigName === $routeName) {
                    return __($item['label']);
                }
            }
        }

        return null;
    }
}

if (! function_exists('buildBreadcrumbFromHistory')) {
    function buildBreadcrumbFromHistory(int $limit = 5): array {
        $history = $_SESSION['url_history'] ?? [];

        $ignoredRoutes = config('breadcrumb.ignored_routes', []); // config pour exclure certaines routes
        $menuConfig = config('menu');
        $tabConfig  = config('tabs');
        $breadcrumb = [];

        $history = array_reverse($history);

        foreach ($history as $url) {
            if (count($breadcrumb) >= $limit) {
                break;
            }

            $parts    = parse_url($url);
            $baseUrl  = $parts['scheme'].'://'.$parts['host'].($parts['path'] ?? '');
            $fragment = $parts['fragment'] ?? null;

            $routeName = getRouteNameFromUrl($baseUrl);

            // ignorer certaines routes
            if ($routeName && in_array($routeName, $ignoredRoutes, true)) {
                continue;
            }

            $label = null;

            // 1) Vérifier dans tabs.php
            if ($routeName && isset($tabConfig[$routeName])) {
                $label = $tabConfig[$routeName];
            }

            // 2) Vérifier dans le menu
            if (!$label && $routeName) {
                $label = findLabelInMenu($routeName, $menuConfig);
            }

            // 3) Vérifier si fragment (#tab) correspond à un libellé
            if ($fragment && isset($tabConfig[$fragment])) {
                $label = $tabConfig[$fragment];
            }

            // 4) Fallback : générer à partir du nom de la route
            if (!$label && $routeName) {
                $label = ucwords(str_replace('.', ' ', $routeName));
            }

            $breadcrumb[] = [
                'url'   => $url,
                'label' => $label ?? $url, // mais normalement, plus jamais $url brut
            ];
        }

        return array_reverse($breadcrumb);
    }
}



