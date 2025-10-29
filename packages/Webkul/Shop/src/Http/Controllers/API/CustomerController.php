<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Webkul\Shop\Http\Requests\Customer\LoginRequest;
use Webkul\Shop\Http\Resources\CustomerResource;

class CustomerController extends APIController
{
    /**
     * Login Customer
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        if (! auth()->guard('customer')->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'message' => trans('shop::app.customers.login-form.invalid-credentials'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->status) {
            auth()->guard('customer')->logout();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.not-activated'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->is_verified) {
            Cookie::queue(Cookie::make('enable-resend', 'true', 1));

            Cookie::queue(Cookie::make('email-for-resend', $request->get('email'), 1));

            auth()->guard('customer')->logout();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.verify-first'),
            ], Response::HTTP_FORBIDDEN);
        }

        /**
         * Event passed to prepare cart after login.
         */
        $customer = auth()->guard('customer')->user();

        Event::dispatch('customer.after.login', $customer);

        // Issue a personal access token for stateless API usage via Sanctum
        $token = $customer->createToken('customer-api')->plainTextToken;

        return (new \Illuminate\Http\Resources\Json\JsonResource([
            'data'    => [
                'customer'   => new CustomerResource($customer),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
            'message' => 'Logged in successfully.',
        ]))->response();
    }

    /**
     * Get the authenticated customer's profile using Sanctum token.
     */
    public function me()
    {
        $customer = auth()->user();

        return (new \Illuminate\Http\Resources\Json\JsonResource([
            'data' => [
                'customer' => new CustomerResource($customer),
            ],
        ]))->response();
    }
}
