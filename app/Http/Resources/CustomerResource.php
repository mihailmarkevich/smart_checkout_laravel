<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'email'            => $this->email,
            'name'             => $this->name,
            'shipping_address' => $this->shipping_address,
            'created_at'       => $this->created_at,
        ];
    }
}
