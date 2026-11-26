<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCustomerSignedIn
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->guest(route('customer.login'))
                ->header('Cache-Control', 'private, no-store')
                ->header('Referrer-Policy', 'no-referrer');
        }

        return $next($request);
    }
}
