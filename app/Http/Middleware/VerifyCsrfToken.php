<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // API routes are now protected with Sanctum authentication (Bearer tokens)
        // and don't require CSRF tokens. Login endpoint excluded as it's used to obtain tokens.
        'api/customer/login',
    ];
}
