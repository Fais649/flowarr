<?php

namespace App\Http\Middleware;

use App\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates API requests with the token generated under
 * Config → Processing. Accepts "Authorization: Bearer <token>" or "X-Api-Key".
 */
class AuthenticateApiToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedHash = Settings::apiTokenHash();
        $provided = $request->bearerToken() ?? $request->header('X-Api-Key');

        if ($expectedHash === null || ! is_string($provided) || ! hash_equals($expectedHash, hash('sha256', $provided))) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
