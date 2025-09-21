<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackHistory
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->method() === 'GET' && !$request->ajax()) {
            $history = $_SESSION['url_history'] ?? [];
            $currentUrl = url()->full(); // inclut aussi le fragment (#...)

            // N'ajouter que si l'historique est vide OU que la dernière URL est différente
            if (empty($history) || end($history) !== $currentUrl) {
                $history[] = $currentUrl;

                // Limiter à 10 entrées
                if (count($history) > 10) {
                    array_shift($history);
                }

                $_SESSION['url_history'] = $history;
            }
        }

        return $next($request);
    }
}
