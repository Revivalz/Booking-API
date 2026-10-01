<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-API-KEY') ?? $request->query('key');

        if (! $providedKey || $providedKey !== env('API_KEY')) {
            return response()->json([
                'message' => 'Unauthorized. Missing or invalid API key.',
            ], 401);
        }

        return $next($request);
    }
}