<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'status'        => $this->status,
            'total_amount'  => $this->total_amount,
            'wms_status'    => $this->wms_status,
            'wms_synced_at' => $this->wms_synced_at,
            'created_at'    => $this->created_at,
            'customer'      => new CustomerResource($this->whenLoaded('customer')),
            'items'         => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
