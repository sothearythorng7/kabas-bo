<?php

use Illuminate\Support\Facades\Route;

if (!function_exists('prepareMenuForJs')) {
    function prepareMenuForJs(array $items): array {
        $out = [];
        foreach ($items as $it) {
            $entry = [
                'label' => isset($it['label']) ? __($it['label']) : '', // <-- traduction ici
                'icon' => $it['icon'] ?? '',
                'method' => $it['method'] ?? null,
                'attributes' => $it['attributes'] ?? [],
                'url' => null,
                'submenu' => []
            ];

            // Génération de l'URL
            if (isset($it['route'])) {
                try {
                    $entry['url'] = is_array($it['route'])
                        ? route($it['route'][0], $it['route'][1] ?? [])
                        : (str_starts_with($it['route'], 'http') || str_starts_with($it['route'], '/')
                            ? $it['route']
                            : route($it['route']));
                } catch (\Exception $e) {
                    $entry['url'] = null;
                }
            }

            // Sous-menus
            if (isset($it['submenu'])) {
                $entry['submenu'] = prepareMenuForJs($it['submenu']);
            } elseif (!empty($it['dynamic_submenu'])) {
                $dyn = is_callable($it['dynamic_submenu']) ? call_user_func($it['dynamic_submenu']) : $it['dynamic_submenu'];
                if (is_array($dyn)) $entry['submenu'] = prepareMenuForJs($dyn);
            }

            $out[] = $entry;
        }
        return $out;
    }
}


if (!function_exists('getActiveMenu')) {
    function getActiveMenu(): array
    {
        $route = Route::currentRouteName();

        $menu = [
            'level0' => null,
            'level1' => null,
            'level2' => null,
        ];

        // Dashboard
        if ($route === 'dashboard') {
            $menu['level0'] = 'dashboard';
        }

        // Stocks
        elseif (str_starts_with($route, 'stocks.') || $route === 'stocks.index' || $route === 'stock-movements.index') {
            $menu['level0'] = 'stocks';
            $menu['level1'] = 'submenu-stock';
        }

        // Comptabilité
        elseif (
            str_starts_with($route, 'warehouse-invoices.') ||
            str_starts_with($route, 'reseller-invoices.') ||
            str_starts_with($route, 'stock-value') ||
            str_starts_with($route, 'stores.')
        ) {
            $menu['level0'] = 'compta';

            // Stores
            if (str_starts_with($route, 'stores.')) {
                $menu['level1'] = 'submenu-stores';

                // Si on a l'ID du store dans la route
                $storeId = request()->route('site') ?? request()->route('store') ?? null;
                if ($storeId) {
                    $menu['level2'] = 'store-' . $storeId;
                }
            }
        }

        // Paramètres
        elseif (
            str_starts_with($route, 'roles.') ||
            str_starts_with($route, 'users.') ||
            str_starts_with($route, 'stores.') ||
            str_starts_with($route, 'categories.') ||
            str_starts_with($route, 'brands.')
        ) {
            $menu['level0'] = 'parametres';
            $menu['level1'] = 'submenu-parametres';
        }

        return $menu;
    }
}
