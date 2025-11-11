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
        'active_pattern' => 'products*|gift-boxes*|gift-cards*|inventory*',
        'submenu' => [
            [
                'label' => 'messages.menu.physical_products',
                'icon'  => 'bi-box-seam',
                'route' => 'products.index',
                'active_pattern' => 'products*',
            ],
            [
                'label' => 'messages.menu.gift_boxes',
                'icon'  => 'bi-gift',
                'route' => 'gift-boxes.index',
                'active_pattern' => 'gift-boxes*',
            ],
            [
                'label' => 'messages.menu.gift_cards',
                'icon'  => 'bi-credit-card',
                'route' => 'gift-cards.index',
                'active_pattern' => 'gift-cards*',
            ],
            [
                'label' => 'messages.menu.inventory',
                'icon'  => 'bi-clipboard-check',
                'route' => 'inventory.index',
                'active_pattern' => 'inventory*',
            ],
        ]
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
            // [
            //     'label' => 'messages.menu.stock_movements',
            //     'icon'  => 'bi-arrow-left-right',
            //     'route' => 'stock-movements.index',
            //     'active_pattern' => 'stock-movements*',
            // ],
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
        'label' => 'Website',
        'icon'  => 'bi-globe',
        'active_pattern' => 'blog*|contact-messages*|pages*|hero-slides*|home-content*|promotion-bar*',
        'submenu' => [
            [
                'label' => 'messages.menu.home_content',
                'icon'  => 'bi-house-heart',
                'route' => 'home-content.edit',
                'active_pattern' => 'home-content*',
            ],
            [
                'label' => 'messages.menu.promotion_bar',
                'icon'  => 'bi-megaphone',
                'route' => 'promotion-bar.index',
                'active_pattern' => 'promotion-bar*',
            ],
            [
                'label' => 'messages.menu.blog',
                'icon'  => 'bi-newspaper',
                'active_pattern' => 'blog*',
                'submenu' => [
                    [
                        'label' => 'messages.menu.blog_posts',
                        'icon'  => 'bi-file-text',
                        'route' => 'blog.posts.index',
                        'active_pattern' => 'blog/posts*',
                    ],
                    [
                        'label' => 'messages.menu.blog_categories',
                        'icon'  => 'bi-folder',
                        'route' => 'blog.categories.index',
                        'active_pattern' => 'blog/categories*',
                    ],
                    [
                        'label' => 'messages.menu.blog_tags',
                        'icon'  => 'bi-tags',
                        'route' => 'blog.tags.index',
                        'active_pattern' => 'blog/tags*',
                    ],
                ]
            ],
            [
                'label' => 'messages.menu.contact_messages',
                'icon'  => 'bi-envelope',
                'route' => 'contact-messages.index',
                'active_pattern' => 'contact-messages*',
            ],
            [
                'label' => 'messages.menu.static_pages',
                'icon'  => 'bi-file-earmark-text',
                'route' => 'admin.pages.index',
                'active_pattern' => 'pages*',
            ],
            [
                'label' => 'messages.menu.banners',
                'icon'  => 'bi-image',
                'route' => 'hero-slides.index',
                'active_pattern' => 'hero-slides*',
            ],
        ]
    ],
    [
        'label' => 'messages.menu.settings',
        'icon'  => 'bi-gear',
        'active_pattern' => 'roles*|users*|stores*|categories*|brands*|variation*|backups*', // parent actif si une sous-page est active
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
            [
                'label' => 'messages.menu.variations',
                'icon'  => 'bi-bezier2',
                'active_pattern' => 'variation*',
                'submenu' => [
                    [
                        'label' => 'messages.menu.variations_types',
                        'icon'  => 'bi-eye',
                        'route' => 'variation-types.index',
                        'active_pattern' => 'variation-types*',
                    ],
                    [
                        'label' => 'messages.menu.variations_values',
                        'icon'  => 'bi-list',
                        'route' => 'variation-values.index',
                        'active_pattern' => 'variation-values*',
                    ],
                ]
            ],
            [
                'label' => 'messages.menu.backups',
                'icon'  => 'bi-hdd-stack',
                'route' => 'backups.index',
                'active_pattern' => 'backups*',
            ],
        ],
    ],
];
