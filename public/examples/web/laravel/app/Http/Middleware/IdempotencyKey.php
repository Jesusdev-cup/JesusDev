<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency middleware: dedups POST retries by Idempotency-Key.
 * Same rule as X-Request-Id — replay returns the stored response.
 */
class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('POST')) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key') ?? $request->header('X-Request-Id');
        if (!$key) {
            // Mandatory: retries of a write must be dedupable by contract.
            return response()->json([
                'message' => 'Idempotency-Key header is required for mutations.',
            ], 400);
        }
        if (strlen($key) > 128 || !preg_match('/^[\w\-:.]+$/', $key)) {
            return response()->json([
                'message' => 'Idempotency-Key must be at most 128 word/dot/colon/hyphen chars.',
            ], 400);
        }

        $cacheKey = "idem:{$key}";
        if (Cache::has($cacheKey)) {
            $stored = Cache::get($cacheKey);
            return response()->json($stored['body'], $stored['status']);
        }

        $response = $next($request);

        Cache::put($cacheKey, [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getContent(), true),
        ], now()->addHours(24));

        return $response;
    }
}
