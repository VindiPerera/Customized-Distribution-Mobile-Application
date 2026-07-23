<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restricts a route to users with the 'admin' role (e.g. managing
     * staff accounts, editing products, deleting records). Staff users
     * can still log in to the portal for day-to-day sales/payments.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'This action requires an administrator account.');
        }

        return $next($request);
    }
}
