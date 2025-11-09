<?php

namespace App\Http\Requests;

class CheckoutRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'customer'                  => ['required', 'array'],
            'customer.email'            => ['required', 'email'],
            'customer.name'             => ['required', 'string', 'max:255'],
            'customer.shipping_address' => ['nullable', 'string', 'max:1000'],

            'items'                     => ['required', 'array', 'min:1'],
            'items.*.product_id'        => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'               => ['required', 'integer', 'min:1'],
        ];
    }

}
