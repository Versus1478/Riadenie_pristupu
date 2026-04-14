<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PremiumOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->hasActivePremium()) {
            return response()->json([
                'message' => 'Táto funkcia je dostupná iba pre prémiových používateľov.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
