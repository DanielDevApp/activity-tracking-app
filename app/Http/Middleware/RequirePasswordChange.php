<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Accounts created with a temporary password must choose their own before using the app. */
class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password && ! $request->routeIs('profile.*', 'logout')) {
            return redirect()->route('profile.edit')
                ->with('notice', 'Welcome. Please choose your own password before you continue.');
        }

        return $next($request);
    }
}
