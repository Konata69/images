<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AuthenticateWithBasicAuthOnlyApi extends AuthenticateWithBasicAuth
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
            return Redirect::to('/');
        }

        return parent::handle($request, $next, $guard, $field);
    }
}
