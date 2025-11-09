<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Customers",
 *     description="Customer listing and their orders"
 * )
 */
class CustomerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/customers",
     *     tags={"Customers"},
     *     summary="List customers",
     *     description="Returns a paginated list of customers.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of customers (paginated)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $customers = Customer::orderByDesc('created_at')->paginate(20);

        return CustomerResource::collection($customers);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/customers/{customer}/orders",
     *     tags={"Customers"},
     *     summary="List orders for a customer",
     *     description="Returns a paginated list of orders for a specific customer.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="customer",
     *         in="path",
     *         required=true,
     *         description="Customer ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of customer's orders (paginated)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function orders(Customer $customer): AnonymousResourceCollection
    {
        $orders = $customer->orders()
            ->with(['items.product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }
}
