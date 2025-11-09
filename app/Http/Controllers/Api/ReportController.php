<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Reports\SalesReportContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="Reporting and analytics endpoints"
 * )
 */
class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/reports/sales.csv",
     *     tags={"Reports"},
     *     summary="Sales report (CSV)",
     *     description="Returns a streamed CSV report with aggregated sales data (per product), optionally filtered by date range.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Start date (YYYY-MM-DD). Filters orders created on or after this date.",
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="End date (YYYY-MM-DD). Filters orders created on or before this date.",
     *         @OA\Schema(type="string", format="date", example="2025-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sales report CSV",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary",
     *                 description="CSV file with columns: sku, product_name, orders_count, total_qty, total_revenue"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function salesCsv(SalesReportRequest $request, SalesReportContract $report): StreamedResponse
    {
        return $report->exportCsv($request->from(), $request->to());
    }
}
