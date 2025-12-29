<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReceptionAuth
{
    /**
     * Handle an incoming request.
     * Verify that reception_user_id exists in session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('reception_user_id')) {
            return redirect()->route('reception.login');
        }

        return $next($request);
    }
}
