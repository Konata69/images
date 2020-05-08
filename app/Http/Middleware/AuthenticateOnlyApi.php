<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateOnlyApi
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param null $guard
     * @param null $field
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null, $field = null)
    {
        if (!in_array(Auth::user()->email, ['admin@test.loc'])) {
            return abort('403');
        }

        return $next($request);
    }
}
