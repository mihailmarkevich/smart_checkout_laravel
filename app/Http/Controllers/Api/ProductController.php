<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\StoreProductRequest;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="Product catalog management"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="List products",
     *     description="Returns a paginated list of products with stock information.",
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
     *         description="Paginated list of products"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with('stockItem')
            ->orderBy('id')
            ->paginate(50);
 
        return ProductResource::collection($products);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     description="Creates a new product with initial stock quantity.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku","name","price","available_qty"},
     *             @OA\Property(
     *                 property="sku",
     *                 type="string",
     *                 maxLength=255,
     *                 example="SKU-12345"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=255,
     *                 example="Wireless Mouse"
     *             ),
     *             @OA\Property(
     *                 property="price",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 example=49.99
     *             ),
     *             @OA\Property(
     *                 property="available_qty",
     *                 type="integer",
     *                 minimum=0,
     *                 example=100
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (e.g. SKU already exists)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */    
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
 
        $product = Product::create([
            'sku'   => $data['sku'],
            'name'  => $data['name'],
            'price' => $data['price'],
        ]);

        StockItem::create([
            'product_id'    => $product->id,
            'available_qty' => $data['available_qty'],
        ]);

        return (new ProductResource($product->load('stockItem')))
            ->response()
            ->setStatusCode(201);
    }
}
