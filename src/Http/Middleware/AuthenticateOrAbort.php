<?php

namespace Mykolavoitovych\ArtisanRunner\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateOrAbort
{
    public function handle(Request $request, Closure $next, string $guard): mixed
    {
        if (! Auth::guard($guard)->check()) {
            abort(404);
        }

        return $next($request);
    }
}