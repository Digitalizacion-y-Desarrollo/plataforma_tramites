<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (blank($token)) {
            return response()->json(['message' => 'Se requiere una API Key mediante el esquema Bearer.'], 401);
        }

        $hash = hash('sha256', $token);
        $apiKey = ApiKey::query()->where('token_hash', $hash)->first();
        if (! $apiKey || ! hash_equals($apiKey->token_hash, $hash) || ! $apiKey->activo || ($apiKey->expira_en && $apiKey->expira_en->isPast())) {
            return response()->json(['message' => 'La API Key no es válida, está revocada o expiró.'], 401);
        }

        $apiKey->forceFill(['ultimo_uso_at' => now()])->save();
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
