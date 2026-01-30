<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBoAccess
{
    /**
     * Roles that are NOT allowed to access the Back Office.
     */
    protected array $blockedRoles = ['user', 'seller'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has any of the blocked roles
        if ($user->hasAnyRole($this->blockedRoles)) {
            abort(403, __('messages.access_denied_bo'));
        }

        return $next($request);
    }
}
