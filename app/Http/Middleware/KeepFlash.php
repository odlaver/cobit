<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Polling tidak boleh menghabiskan pesan flash
class KeepFlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        return $response;
    }
}
