<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isAdmin()) {
            return response()->json([
                'message' => 'Prístup je povolený len administrátorovi.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
