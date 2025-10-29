<?php

namespace Webkul\Shop\Http\Middleware;

use Closure;

class AuthenticateCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = 'customer')
    {
        $sessionGuard = auth()->guard($guard);
        $tokenGuard = auth('sanctum');

        $user = $sessionGuard->user() ?: $tokenGuard->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '',
                ], 401);
            }

            return redirect()->route('shop.customer.session.index');
        } else {
            if (! $user->status) {
                $sessionGuard->logout();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => trans('shop::app.customers.login-form.not-activated'),
                    ], 401);
                }

                session()->flash('warning', trans('shop::app.customers.login-form.not-activated'));

                return redirect()->route('shop.customer.session.index');
            }
        }

        return $next($request);
    }
}
