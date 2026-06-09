<?php

namespace App\Http\Middleware;

use App\Support\Honeypot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectBots
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Honeypot::isBot($request)) {
            return Honeypot::fakeResponse($request);
        }

        return $next($request);
    }
}
