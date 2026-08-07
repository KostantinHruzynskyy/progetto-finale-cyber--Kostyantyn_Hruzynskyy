<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventClickjacking
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none';");
        return $response;
    }
}