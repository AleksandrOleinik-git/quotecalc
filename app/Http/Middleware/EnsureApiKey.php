<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the REST API with a shared key from QUOTECALC_API_KEY, sent as "X-Api-Key".
 * An unset key locks the API instead of leaving it open.
 */
class EnsureApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('quotecalc.api_key');
        $given = (string) $request->header('X-Api-Key');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Invalid or missing API key.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
