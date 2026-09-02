<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

class EnsureUserHasPermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        try {
            $required = Permission::from($permission);
        } catch (ValueError) {
            abort(403);
        }

        abort_unless($request->user()?->hasPermission($required) ?? false, 403);

        return $next($request);
    }
}
