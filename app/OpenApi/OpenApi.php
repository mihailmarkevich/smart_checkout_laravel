<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="SmartCheckout API",
 *     version="1.0.0",
 *     description="API for SmartCheckout demo"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Local development server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class OpenApi
{
    //
}
