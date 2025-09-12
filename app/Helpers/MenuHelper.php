<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class MenuHelper
{
    public static function getActiveMenu()
    {
        $route = Route::currentRouteName();

        $menu = [
            'level0' => null,
            'level1' => null,
            'level2' => null,
        ];

        // Dashboard
        if($route === 'dashboard') {
            $menu['level0'] = 'dashboard';
        }

        // Stocks
        elseif(str_starts_with($route, 'stocks.') || $route === 'stocks.index' || $route === 'stock-movements.index') {
            $menu['level0'] = 'stocks';
            $menu['level1'] = 'submenu-stock';
        }

        // Comptabilité
        elseif(str_starts_with($route, 'warehouse-invoices.') ||
               str_starts_with($route, 'reseller-invoices.') ||
               str_starts_with($route, 'stock-value') ||
               str_starts_with($route, 'stores.')) 
        {
            $menu['level0'] = 'compta';
            
            // Stores
            if(str_starts_with($route, 'stores.')) {
                $menu['level1'] = 'submenu-stores';
                
                // Si on a l'ID du store dans la route
                $storeId = request()->route('site') ?? request()->route('store') ?? null;
                if($storeId) {
                    $menu['level2'] = 'store-' . $storeId;
                }
            }
        }

        // Paramètres
        elseif(str_starts_with($route, 'roles.') ||
               str_starts_with($route, 'users.') ||
               str_starts_with($route, 'stores.') ||
               str_starts_with($route, 'categories.') ||
               str_starts_with($route, 'brands.'))
        {
            $menu['level0'] = 'parametres';
            $menu['level1'] = 'submenu-parametres';
        }

        return $menu;
    }
}
