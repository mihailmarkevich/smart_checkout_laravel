<?php

namespace App\Http\Requests;

use Illuminate\Support\Carbon;

class SalesReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['nullable','date_format:Y-m-d'],
            'date_to'   => ['nullable','date_format:Y-m-d','after_or_equal:date_from'],
        ];
    }

    public function from(): ?Carbon
    {
        return $this->filled('date_from') ? Carbon::createFromFormat('Y-m-d', $this->string('date_from')) : null;
    }

    public function to(): ?Carbon
    {
        return $this->filled('date_to') ? Carbon::createFromFormat('Y-m-d', $this->string('date_to')) : null;
    }
}
