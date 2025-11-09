<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly JwtService $jwtService
    ) {
    }

    public function handle(Request $request, Closure $next): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $header = $request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $token = substr($header, 7);

        $payload = $this->jwtService->decode($token);

        if (! $payload || ! isset($payload['sub'])) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = User::find($payload['sub']);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
