<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * @OA\Tag(
 *     name="Health",
 *     description="Service health checks"
 * )
 */
class HealthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/health",
     *     tags={"Health"},
     *     summary="Health check",
     *     description="Returns overall service status and basic checks for database and Redis.",
     *     @OA\Response(
     *         response=200,
     *         description="Service is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(property="database", type="string", example="ok"),
     *                 @OA\Property(property="redis", type="string", example="ok")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="One or more dependencies are unhealthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="degraded"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(property="database", type="string", example="error"),
     *                 @OA\Property(property="redis", type="string", example="ok")
     *             )
     *         )
     *     )
     * )
     */
    public function __invoke()
    {
        $status = 'ok';
        $checks = [
            'database' => 'ok',
            'redis'    => 'ok',
        ];

        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            $status = 'degraded';
            $checks['database'] = 'error';
        }

        try {
            Redis::connection()->ping();
        } catch (\Throwable $e) {
            $status = 'degraded';
            $checks['redis'] = 'error';
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
        ], $status === 'ok' ? 200 : 500);
    }
}
