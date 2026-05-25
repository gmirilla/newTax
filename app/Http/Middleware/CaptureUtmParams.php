<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureUtmParams
{
    private const COOKIE_NAME = 'acq_params';
    private const COOKIE_DAYS = 30;

    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            $ref = $request->query('ref');
            $utm = array_filter([
                'utm_source'   => $request->query('utm_source'),
                'utm_medium'   => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'ref'          => $ref,
            ]);

            if (!empty($utm)) {
                // Merge with existing cookie so a later UTM click doesn't wipe a referral code
                $existing = [];
                if ($raw = $request->cookie(self::COOKIE_NAME)) {
                    $existing = json_decode($raw, true) ?? [];
                }

                $merged   = array_merge($existing, $utm);
                $response = $next($request);

                return $response->withCookie(
                    cookie(self::COOKIE_NAME, json_encode($merged), self::COOKIE_DAYS * 24 * 60)
                );
            }
        }

        return $next($request);
    }

    public static function read(Request $request): array
    {
        if ($raw = $request->cookie(self::COOKIE_NAME)) {
            return json_decode($raw, true) ?? [];
        }

        return [];
    }
}
