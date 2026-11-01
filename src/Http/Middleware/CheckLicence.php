<?php

namespace Epixel\LicenceGuard\Http\Middleware;

use Closure;
use Epixel\LicenceGuard\LicenceChecker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicence
{
    public function handle(Request $request, Closure $next): Response
    {
        $product = LicenceChecker::product();
        if ($product !== '') {
            LicenceChecker::check($product);
        }

        return $next($request);
    }
}
