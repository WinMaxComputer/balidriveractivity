<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureVerifiedCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->guest(route('customer.login'));
        }
        if (!$request->user()->email_verified_at) {
            if (!$request->isMethod('GET') || $request->expectsJson()) {
                abort(403, 'Verify your email before viewing your bookings.');
            }

            return redirect()->route('customer.verify.notice');
        }

        return $next($request);
    }
}
