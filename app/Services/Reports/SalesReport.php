<?php

namespace App\Services\Reports;

use App\Contracts\Reports\SalesReportContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport implements SalesReportContract
{
    public function exportCsv(?Carbon $from, ?Carbon $to): StreamedResponse
    {
        $query = DB::table('orders')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('
                products.sku as sku,
                products.name as product_name,
                COUNT(DISTINCT orders.id) as orders_count,
                SUM(order_items.qty) as total_qty,
                SUM(order_items.qty * order_items.price) as total_revenue
            ')
            ->where('orders.status', '=', 'paid');

        if ($from) {
            $query->whereDate('orders.created_at', '>=', $from->toDateString());
        }
        if ($to) {
            $query->whereDate('orders.created_at', '<=', $to->toDateString());
        }

        $query->groupBy('products.sku', 'products.name')
              ->orderByDesc('total_revenue');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, ['sku','product_name','orders_count','total_qty','total_revenue']);

            // Stream in chunks to keep memory low
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    // Basic CSV injection hardening: prefix if starts with =,+,-,@
                    $sku = $this->sanitizeCsv($r->sku);
                    $name = $this->sanitizeCsv($r->product_name);

                    fputcsv($out, [
                        $sku,
                        $name,
                        (int) $r->orders_count,
                        (int) $r->total_qty,
                        number_format((float) $r->total_revenue, 2, '.', ''),
                    ]);
                }
            });

            fclose($out);
        }, 'sales_report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function sanitizeCsv(?string $val): ?string
    {
        if ($val === null) return null;
        return preg_match('/^[=\+\-@]/', $val) ? "'".$val : $val;
    }
}
