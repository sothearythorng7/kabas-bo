<?php

return [
    [
        'label' => 'messages.menu.dashboard',
        'icon'  => 'bi-house-door',
        'route' => 'dashboard',
    ],
    [
        'label' => 'messages.menu.catalog',
        'icon'  => 'bi-list-check',
        'route' => 'products.index',
    ],
    [
        'label' => 'messages.menu.stocks',
        'icon'  => 'bi-inboxes',
        'submenu' => [
            [
                'label' => 'messages.menu.stock_overview',
                'icon'  => 'bi-eye',
                'route' => 'stocks.index',
            ],
            [
                'label' => 'messages.menu.stock_movements',
                'icon'  => 'bi-arrow-left-right',
                'route' => 'stock-movements.index',
            ],
        ]
    ],
    [
        'label' => 'messages.menu.resellers',
        'icon'  => 'bi-shop',
        'route' => 'resellers.index',
    ],
    [
        'label' => 'messages.menu.suppliers',
        'icon'  => 'bi-truck',  
        'submenu' => [
            [
                'label' => 'messages.menu.suppliers_overview',
                'icon'  => 'bi-eye',
                'route' => 'supplier-orders.overview',
            ],
            [
                'label' => 'messages.menu.suppliers_list',
                'icon'  => 'bi-list',
                'route' => 'suppliers.index',
            ],
        ]
    ],
    [
        'label' => 'messages.menu.accounting',
        'icon'  => 'bi-truck',
        'dynamic_submenu' => function() {
            $staticLinks = [
                [
                    'label' => 'messages.menu.supplier_payments',
                    'icon'  => 'bi-star',
                    'route' => 'reseller-invoices.index',
                ],
            ];
            $dynamicLinks = \App\Models\Store::all()->map(function($store){
                return [
                    'label' => $store->name,
                    'icon'  => 'bi-journal-text',
                    'route' => ['financial.dashboard', ['store' => $store->id]],
                ];
            })->toArray();
            return array_merge($staticLinks, $dynamicLinks);
        }
    ],
    // Nouveau menu pour la configuration
    [
        'label' => 'messages.menu.settings',
        'icon'  => 'bi-gear',
        'submenu' => [
            [
                'label' => 'messages.menu.roles',
                'icon'  => 'bi-shield-lock',
                'route' => 'roles.index',
            ],
            [
                'label' => 'messages.menu.users',
                'icon'  => 'bi-people',
                'route' => 'users.index',
            ],
            [
                'label' => 'messages.menu.stores',
                'icon'  => 'bi-shop-window',
                'route' => 'stores.index',
            ],
            [
                'label' => 'messages.menu.categories',
                'icon'  => 'bi-tags',
                'route' => 'categories.index',
            ],
            [
                'label' => 'messages.menu.brands',
                'icon'  => 'bi-award',
                'route' => 'brands.index',
            ],
        ],
    ],
];
