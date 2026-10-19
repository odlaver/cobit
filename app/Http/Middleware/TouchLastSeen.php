<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TouchLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()?->touchLastSeen();

        return $next($request);
    }
}
