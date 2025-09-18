<?php

return [
    [
        'label' => 'messages.menu.dashboard',
        'icon'  => 'bi-house-door',
        'route' => 'dashboard',
        'active_pattern' => 'dashboard*',
    ],
    [
        'label' => 'messages.menu.catalog',
        'icon'  => 'bi-list-check',
        'route' => 'products.index',
        'active_pattern' => 'products*',
    ],
    [
        'label' => 'messages.menu.stocks',
        'icon'  => 'bi-inboxes',
        'active_pattern' => 'stocks*',
        'submenu' => [
            [
                'label' => 'messages.menu.stock_overview',
                'icon'  => 'bi-eye',
                'route' => 'stocks.index',
                'active_pattern' => 'stocks*',
            ],
            [
                'label' => 'messages.menu.stock_movements',
                'icon'  => 'bi-arrow-left-right',
                'route' => 'stock-movements.index',
                'active_pattern' => 'stock-movements*',
            ],
        ]
    ],
    [
        'label' => 'messages.menu.resellers',
        'icon'  => 'bi-shop',
        'route' => 'resellers.index',
        'active_pattern' => 'resellers*',
    ],
    [
        'label' => 'messages.menu.suppliers',
        'icon'  => 'bi-truck',
        'active_pattern' => 'suppliers*', // parent actif pour toutes les sous-pages
        'submenu' => [
            [
                'label' => 'messages.menu.suppliers_overview',
                'icon'  => 'bi-eye',
                'route' => 'supplier-orders.overview',
                'active_pattern' => 'supplier-orders*',
            ],
            [
                'label' => 'messages.menu.suppliers_list',
                'icon'  => 'bi-list',
                'route' => 'suppliers.index',
                // pas besoin d'active_pattern ici, parent gère les sous-pages
            ],
        ]
    ],
    [
        'label' => 'messages.menu.accounting',
        'icon'  => 'bi-currency-dollar',
        'dynamic_submenu' => function() {
            $staticLinks = [
                [
                    'label' => 'messages.menu.invoice_overview',
                    'icon'  => 'bi-eyeglasses',
                    'route' => 'financial.overview',
                    'active_pattern' => 'financial/overview*',
                ],
            ];
            $dynamicLinks = \App\Models\Store::all()->map(function($store){
                return [
                    'label'          => $store->name,
                    'icon'           => 'bi-house-door-fill',
                    'route'          => ['financial.dashboard', ['store' => $store->id]],
                    'active_pattern' => "financial/{$store->id}/*",
                ];
            })->toArray();
            return array_merge($staticLinks, $dynamicLinks);
        }
    ],
    [
        'label' => 'messages.menu.settings',
        'icon'  => 'bi-gear',
        'active_pattern' => 'roles*|users*|stores*|categories*|brands*', // parent actif si une sous-page est active
        'submenu' => [
            [
                'label' => 'messages.menu.roles',
                'icon'  => 'bi-shield-lock',
                'route' => 'roles.index',
                'active_pattern' => 'roles*',
            ],
            [
                'label' => 'messages.menu.users',
                'icon'  => 'bi-people',
                'route' => 'users.index',
                'active_pattern' => 'users*',
            ],
            [
                'label' => 'messages.menu.stores',
                'icon'  => 'bi-shop-window',
                'route' => 'stores.index',
                'active_pattern' => 'stores*',
            ],
            [
                'label' => 'messages.menu.categories',
                'icon'  => 'bi-tags',
                'route' => 'categories.index',
                'active_pattern' => 'categories*',
            ],
            [
                'label' => 'messages.menu.brands',
                'icon'  => 'bi-award',
                'route' => 'brands.index',
                'active_pattern' => 'brands*',
            ],
        ],
    ],
];
