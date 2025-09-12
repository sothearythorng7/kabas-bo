<?php

use Illuminate\Support\Facades\Route;

if (! function_exists('generateBreadcrumbs')) {
    function generateBreadcrumbs()
    {
        $routeName = Route::currentRouteName();
        if (!$routeName) return [];

        $segments = explode('.', $routeName);
        $breadcrumbs = [];
        $urlParts = [];

        foreach ($segments as $segment) {
            $urlParts[] = $segment;
            $routeCandidate = implode('.', $urlParts);
            
            $breadcrumbs[] = [
                'label' => ucfirst(str_replace('-', ' ', $segment)),
                'url' => Route::has($routeCandidate) ? route($routeCandidate) : '#',
            ];
        }

        return $breadcrumbs;
    }
}
