<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates tour operator partners by the X-Api-Key header.
 * The key is hashed and compared with the stored hash; the plain key is never stored.
 */
class AuthenticatePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('X-Api-Key');

        $partner = $key === ''
            ? null
            : Partner::where('api_key_hash', Partner::hashKey($key))->where('active', true)->first();

        if (! $partner) {
            return response()->json(['message' => 'Invalid or missing API key.', 'error' => 'unauthenticated'], 401);
        }

        $request->attributes->set('partner', $partner);

        return $next($request);
    }
}
