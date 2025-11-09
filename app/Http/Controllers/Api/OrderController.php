<?php

namespace App\Http\Controllers\Api;

use App\Contracts\OrderServiceContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\SyncOrderToWms;
use App\Models\Order;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management and checkout"
 * )
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceContract $orderService
    ) {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     tags={"Orders"},
     *     summary="List orders",
     *     description="Returns a paginated list of orders with optional filters.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status (e.g. pending, paid, cancelled)",
     *         required=false,
     *         @OA\Schema(type="string", example="paid")
     *     ),
     *     @OA\Parameter(
     *         name="customer_email",
     *         in="query",
     *         description="Filter by customer email",
     *         required=false,
     *         @OA\Schema(type="string", example="john@example.com")
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
     *         description="Paginated list of orders"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::query()
            ->with(['customer', 'items.product'])
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($email = $request->query('customer_email')) {
            $query->whereHas('customer', function ($q) use ($email) {
                $q->where('email', $email);
            });
        }

        $orders = $query->paginate(20);

        return OrderResource::collection($orders);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/checkout",
     *     tags={"Orders"},
     *     summary="Checkout and create an order",
     *     description="Creates a new order from the given cart/customer data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="customer",
     *                 type="object",
     *                 description="Customer data (existing or new)",
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="shipping_address", type="string", example="Some street 1")
     *             ),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="qty", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="payment_method",
     *                 type="string",
     *                 example="card"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function checkout(CheckoutRequest $request): OrderResource
    {
        $order = $this->orderService->checkout($request->validated());

        return new OrderResource($order->load(['items.product', 'customer']));
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{order}",
     *     tags={"Orders"},
     *     summary="Get order details",
     *     description="Returns details of a single order.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['items.product', 'customer']));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{order}/cancel",
     *     tags={"Orders"},
     *     summary="Cancel an order",
     *     description="Cancels an order if it's in a cancellable state.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Order cannot be cancelled (business rule violation)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function cancel(Order $order): JsonResponse
    {
        try {
            $order = $this->orderService->cancel($order);

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(200);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{order}/resync-wms",
     *     tags={"Orders"},
     *     summary="Resync order to WMS",
     *     description="Dispatches a background job to resync the order to the WMS if it's eligible.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="WMS sync dispatched",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="WMS sync dispatched")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Order is not eligible for WMS sync (already synced or not paid)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function resyncWms(Order $order): JsonResponse
    {
        if ($order->wms_status === 'synced') {
            return response()->json([
                'message' => 'Order already synced to WMS',
            ], 422);
        }

        if ($order->status !== 'paid') {
            return response()->json([
                'message' => 'Only paid orders can be synced to WMS',
            ], 422);
        }

        SyncOrderToWms::dispatch($order);

        return response()->json([
            'message' => 'WMS sync dispatched',
        ]);
    }
}
