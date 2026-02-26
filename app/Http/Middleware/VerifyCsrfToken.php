<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        "keyword-store",
        "store-keyword-planner",
        "auto-keyword-fetch",
        "aio-keyword-fetch",
        "keyword-store-more",
        "median-display",
        "fetch-keyword-planner-keywords",
    ];
}
