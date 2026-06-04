<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnabledMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->enabled) {
            Auth::logout();

            return redirect('/login')
                ->withErrors([
                    'email' => 'Your account has been disabled by administrator.'
                ]);
        }

        return $next($request);
    }
}
