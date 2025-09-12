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
        'route' => 'suppliers.index',
    ],
    [
        'label' => 'messages.menu.accounting',
        'icon' => 'bi-truck',
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
    ]
];
