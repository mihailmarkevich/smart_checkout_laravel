<?php

namespace App\Contracts\Reports;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;

interface SalesReportContract
{
    /**
     * Sales report per product in csv
     */
    public function exportCsv(?Carbon $from, ?Carbon $to): StreamedResponse;
}
