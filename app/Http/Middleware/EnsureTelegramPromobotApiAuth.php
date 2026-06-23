<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTelegramPromobotApiAuth
{
    public const HEADER = 'X-Telegram-Bot-Api-Secret-Token';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('telegram_promobot.api_secret');

        if (! is_string($secret) || $secret === '') {
            abort(503, 'Telegram promobot API is not configured.');
        }

        $token = $request->header(self::HEADER);

        if (! is_string($token) || ! hash_equals($secret, $token)) {
            abort(401);
        }

        return $next($request);
    }
}
